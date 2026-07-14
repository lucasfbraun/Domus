<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Contract;
use App\Services\ChargeScheduler;
use App\Services\ContractDocumentService;
use App\Services\MercadoPagoService;
use App\Services\ReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChargeController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Charge::class);

        return Inertia::render('admin/charges/Index', [
            'charges' => Charge::query()
                ->with(['contract.tenant', 'contract.property', 'receiver'])
                ->orderByDesc('due_date')
                ->get()
                ->map(fn (Charge $charge) => [
                    'id' => $charge->id,
                    'description' => $charge->reference,
                    'amount' => (float) $charge->original_amount,
                    'rateio_amount' => (float) ($charge->rateio_amount ?? 0),
                    'status' => $charge->status?->value ?? $charge->status,
                    'due_date' => $charge->due_date?->toDateString(),
                    'tenant' => $charge->contract?->tenant
                        ? ['name' => $charge->contract->tenant->name]
                        : null,
                    'property' => $charge->contract?->property
                        ? ['name' => $charge->contract->property->name]
                        : null,
                    'receiver' => $charge->receiver
                        ? ['name' => $charge->receiver->name]
                        : null,
                ]),
        ]);
    }

    public function generate(Contract $contract, ChargeScheduler $scheduler): RedirectResponse
    {
        $this->authorize('create', Charge::class);

        $result = $scheduler->generateChargeForContract($contract);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result['created'] ? 'Cobranca gerada.' : ($result['updated'] ? 'Cobranca atualizada.' : 'Cobranca ja paga para este mes.'),
        ]);

        return back();
    }

    public function createPix(Request $request, Charge $charge, MercadoPagoService $mercadoPago): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $charge);

        $result = $mercadoPago->createPixCharge($charge);

        if ($request->expectsJson() || $request->header('X-Inertia-HTTP') || $request->wantsJson()) {
            return response()->json([
                'qr_code' => $result['qrCode'] ?? $charge->fresh()->pix_qr_code,
                'copy_paste' => $result['qrCode'] ?? $charge->fresh()->pix_qr_code,
                'qr_code_base64' => $result['qrCodeBase64'] ?? $charge->fresh()->pix_qr_code_base64,
                'expires_at' => $result['expiresAt'] ?? $charge->fresh()->pix_expires_at,
                'order_id' => $result['orderId'] ?? $charge->fresh()->mercado_pago_order_id,
                'transaction_id' => $result['transactionId'] ?? $charge->fresh()->mercado_pago_transaction_id,
                'ticket_url' => $result['ticketUrl'] ?? $charge->fresh()->payment_url,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pix gerado.']);

        return back();
    }

    public function syncPayment(Request $request, Charge $charge, MercadoPagoService $mercadoPago, ReminderService $reminderService): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $charge);

        $result = $mercadoPago->syncChargePayment($charge);

        if ($result['updated']) {
            $reminderService->sendPaymentConfirmedReminder($charge->fresh());
        }

        if ($request->expectsJson() || $request->header('X-Inertia-HTTP') || $request->wantsJson()) {
            return response()->json($result);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pagamento verificado: '.$result['status']]);

        return back();
    }

    public function sendReminder(Charge $charge, ReminderService $reminderService): RedirectResponse
    {
        $this->authorize('update', $charge);

        $result = $reminderService->sendChargeReminder($charge);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lembrete enviado para '.$result['tenantName']]);

        return back();
    }

    public function receipt(Charge $charge, ContractDocumentService $documentService): BinaryFileResponse
    {
        $this->authorize('view', $charge);

        $path = $documentService->generateReceiptPdf($charge->contract, $charge);
        $fullPath = Storage::disk('local')->path($path);

        return response()->download($fullPath, 'recibo-'.$charge->id.'.pdf')->deleteFileAfterSend();
    }
}
