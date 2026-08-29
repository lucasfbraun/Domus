<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Contract;
use App\Policies\ChargePolicy;
use App\Services\ChargeScheduler;
use App\Services\ContractDocumentService;
use App\Services\MercadoPagoService;
use App\Services\ReminderService;
use App\Support\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Manages rent Charge records (list, Pix generation/sync, reminders,
 * receipt PDF). See {@see ChargePolicy}: besides Admin, a
 * Tenant can view/update charges on their own contract and a Receiver can
 * view charges they are the payment recipient of, so these endpoints are
 * also reachable outside the admin UI proper.
 */
class ChargeController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Charge::class);

        return Inertia::render('admin/charges/Index', [
            'charges' => Charge::query()
                ->with(['contract.tenant', 'contract.property', 'receiver'])
                ->orderByDesc('due_date')
                ->paginate(Pagination::PER_PAGE)
                ->withQueryString()
                ->through(fn (Charge $charge) => [
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

    /**
     * Requests a Pix charge from Mercado Pago for this charge. Responds with
     * JSON (qr code, ticket url, computed amount due) for XHR/Inertia
     * partial requests, or a flashed toast + redirect for a full page
     * request.
     */
    public function createPix(Request $request, Charge $charge, MercadoPagoService $mercadoPago): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $charge);

        try {
            $result = $mercadoPago->createPixCharge($charge);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            report($exception);

            $message = $this->friendlyPixErrorMessage($exception);
            $status = $exception instanceof \InvalidArgumentException ? 422 : 502;

            if ($request->expectsJson() || $request->header('X-Inertia-HTTP') || $request->wantsJson()) {
                return response()->json(['message' => $message], $status);
            }

            Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

            return back();
        }

        if ($request->expectsJson() || $request->header('X-Inertia-HTTP') || $request->wantsJson()) {
            return response()->json([
                'qr_code' => $result['qrCode'] ?? $charge->fresh()->pix_qr_code,
                'copy_paste' => $result['qrCode'] ?? $charge->fresh()->pix_qr_code,
                'qr_code_base64' => $result['qrCodeBase64'] ?? $charge->fresh()->pix_qr_code_base64,
                'expires_at' => $result['expiresAt'] ?? $charge->fresh()->pix_expires_at,
                'order_id' => $result['orderId'] ?? $charge->fresh()->mercado_pago_order_id,
                'transaction_id' => $result['transactionId'] ?? $charge->fresh()->mercado_pago_transaction_id,
                'ticket_url' => $result['ticketUrl'] ?? $charge->fresh()->payment_url,
                'amount_due' => $mercadoPago->computeCurrentAmountDue($charge->fresh(['contract'])),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pix gerado.']);

        return back();
    }

    /**
     * Translates a raw Mercado Pago exception message into a Portuguese
     * message safe to show the user, recognizing known sandbox/credential
     * error signatures.
     */
    private function friendlyPixErrorMessage(\Throwable $exception): string
    {
        $raw = $exception->getMessage();

        if (str_contains($raw, 'processing_error') || str_contains($raw, '(402)')) {
            return 'O Mercado Pago recusou gerar este Pix (erro de processamento). No sandbox, valores acima de R$ 1.000 costumam falhar — em produção o valor com juros/multa deve funcionar normalmente.';
        }

        if (
            str_contains($raw, 'invalid_credentials')
            || str_contains($raw, 'Test credentials are not supported')
            || str_contains($raw, 'credenciais de teste')
            || str_contains($raw, 'TEST-')
        ) {
            return 'O Mercado Pago rejeitou as credenciais de teste. A Orders API exige token de produção (APP_USR-): defina MP_SANDBOX_CONNECT=false no servidor e reconecte o recebedor.';
        }

        if (str_contains($raw, 'PA_UNAUTHORIZED_RESULT_FROM_POLICIES') || str_contains($raw, 'PolicyAgent')) {
            return 'O Mercado Pago bloqueou a criação do Pix (conta/credencial sem permissão). Use o recebedor com OAuth de produção conectado e chave Pix cadastrada na conta MP.';
        }

        if (str_contains($raw, 'invalid_email_for_sandbox')) {
            return 'No sandbox o e-mail do pagador precisa terminar com @testuser.com.';
        }

        if ($exception instanceof \InvalidArgumentException) {
            return $raw;
        }

        return 'Não foi possível gerar o Pix agora. Tente novamente em instantes.';
    }

    /**
     * Polls Mercado Pago for this charge's current Pix payment status. When
     * the sync marks the charge as newly paid, sends the tenant/receiver a
     * payment-confirmed notification via {@see ReminderService}.
     */
    public function syncPayment(Request $request, Charge $charge, MercadoPagoService $mercadoPago, ReminderService $reminderService): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $charge);

        try {
            $result = $mercadoPago->syncChargePayment($charge);
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
