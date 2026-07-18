<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepositStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepositRequest;
use App\Http\Requests\Admin\UpdateDepositRequest;
use App\Models\Contract;
use App\Models\Deposit;
use App\Models\Receiver;
use App\Services\MercadoPagoService;
use App\Support\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepositController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Deposit::class);

        return Inertia::render('admin/deposits/Index', [
            'deposits' => Deposit::query()
                ->with(['contract.tenant', 'contract.property', 'receiver'])
                ->orderByDesc('due_date')
                ->paginate(Pagination::PER_PAGE)
                ->withQueryString()
                ->through(fn (Deposit $deposit) => [
                    'id' => $deposit->id,
                    'description' => $deposit->description,
                    'amount' => (float) $deposit->amount,
                    'status' => $deposit->status?->value ?? $deposit->status,
                    'due_date' => $deposit->due_date?->toDateString(),
                    'paid_at' => $deposit->paid_at?->toDateString(),
                    'refunded_at' => $deposit->refunded_at?->toDateString(),
                    'refunded_amount' => $deposit->refunded_amount !== null ? (float) $deposit->refunded_amount : null,
                    'contract_id' => $deposit->contract_id,
                    'tenant' => $deposit->contract?->tenant
                        ? ['name' => $deposit->contract->tenant->name]
                        : null,
                    'property' => $deposit->contract?->property
                        ? ['name' => $deposit->contract->property->name]
                        : null,
                    'receiver' => $deposit->receiver
                        ? ['name' => $deposit->receiver->name]
                        : null,
                ]),
            'contracts' => Contract::query()
                ->with(['tenant', 'property'])
                ->orderByDesc('starts_at')
                ->get()
                ->map(fn (Contract $contract) => [
                    'id' => $contract->id,
                    'label' => trim(($contract->tenant?->name ?? 'Sem inquilino').' — '.($contract->property?->name ?? 'Sem imovel')),
                ]),
            'receivers' => Receiver::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreDepositRequest $request): RedirectResponse
    {
        $this->authorize('create', Deposit::class);

        Deposit::query()->create($request->validated() + ['status' => DepositStatus::Pending]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Caução cadastrada.']);

        return to_route('admin.deposits.index');
    }

    public function update(UpdateDepositRequest $request, Deposit $deposit): RedirectResponse
    {
        $this->authorize('update', $deposit);

        $deposit->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Caução atualizada.']);

        return to_route('admin.deposits.index');
    }

    public function destroy(Deposit $deposit): RedirectResponse
    {
        $this->authorize('delete', $deposit);

        $deposit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Caução removida.']);

        return to_route('admin.deposits.index');
    }

    public function markRefunded(Request $request, Deposit $deposit): RedirectResponse
    {
        $this->authorize('update', $deposit);

        if ($deposit->status !== DepositStatus::Paid) {
            return back()->withErrors([
                'refund' => 'Só é possível devolver uma caução que já foi paga.',
            ]);
        }

        $data = $request->validate([
            'refunded_amount' => ['nullable', 'numeric', 'min:0.01'],
            'refund_note' => ['nullable', 'string', 'max:500'],
        ]);

        $deposit->update([
            'status' => DepositStatus::Refunded,
            'refunded_at' => now(),
            'refunded_amount' => $data['refunded_amount'] ?? $deposit->amount,
            'refund_note' => $data['refund_note'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Caução marcada como devolvida.']);

        return back();
    }

    public function createPix(Request $request, Deposit $deposit, MercadoPagoService $mercadoPago): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $deposit);

        try {
            $result = $mercadoPago->createPixForDeposit($deposit);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            report($exception);

            $message = $exception instanceof \InvalidArgumentException
                ? $exception->getMessage()
                : 'Não foi possível gerar o Pix agora. Tente novamente em instantes.';
            $status = $exception instanceof \InvalidArgumentException ? 422 : 502;

            if ($request->expectsJson() || $request->header('X-Inertia-HTTP') || $request->wantsJson()) {
                return response()->json(['message' => $message], $status);
            }

            Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

            return back();
        }

        if ($request->expectsJson() || $request->header('X-Inertia-HTTP') || $request->wantsJson()) {
            return response()->json([
                'qr_code' => $result['qrCode'] ?? $deposit->fresh()->pix_qr_code,
                'copy_paste' => $result['qrCode'] ?? $deposit->fresh()->pix_qr_code,
                'qr_code_base64' => $result['qrCodeBase64'] ?? $deposit->fresh()->pix_qr_code_base64,
                'expires_at' => $result['expiresAt'] ?? $deposit->fresh()->pix_expires_at,
                'order_id' => $result['orderId'] ?? $deposit->fresh()->mercado_pago_order_id,
                'transaction_id' => $result['transactionId'] ?? $deposit->fresh()->mercado_pago_transaction_id,
                'ticket_url' => $result['ticketUrl'] ?? $deposit->fresh()->payment_url,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pix gerado.']);

        return back();
    }

    public function syncPayment(Request $request, Deposit $deposit, MercadoPagoService $mercadoPago): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $deposit);

        try {
            $result = $mercadoPago->syncDepositPayment($deposit);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            report($exception);

            $message = $exception instanceof \InvalidArgumentException
                ? $exception->getMessage()
                : 'Não foi possível verificar o pagamento agora. Tente novamente em instantes.';

            if ($request->expectsJson() || $request->header('X-Inertia-HTTP') || $request->wantsJson()) {
                return response()->json(['message' => $message], $exception instanceof \InvalidArgumentException ? 422 : 502);
            }

            Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

            return back();
        }

        if ($request->expectsJson() || $request->header('X-Inertia-HTTP') || $request->wantsJson()) {
            return response()->json($result);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pagamento verificado: '.$result['status']]);

        return back();
    }
}
