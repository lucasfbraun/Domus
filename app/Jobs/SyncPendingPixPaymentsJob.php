<?php

namespace App\Jobs;

use App\Enums\ChargeStatus;
use App\Enums\DepositStatus;
use App\Models\Charge;
use App\Models\Deposit;
use App\Models\PixSyncSetting;
use App\Services\MercadoPagoService;
use App\Services\PixSyncScheduleService;
use App\Services\ReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Scheduled every minute (see routes/console.php) — a no-op most ticks.
 * {@see PixSyncScheduleService::isDue()} checks the admin-configured
 * enabled flag/interval on {@see PixSyncSetting} (Admin ->
 * Configurações) and only actually polls Mercado Pago when a sync is due.
 *
 * Polls Mercado Pago for every Charge/Deposit that has a Pix order still
 * unconfirmed — the automatic counterpart to the admin's manual
 * "Sincronizar pagamento" button (ChargeController/DepositController::
 * syncPayment()). Useful whenever no webhook is configured (e.g. local dev
 * without a public URL) or as a safety net alongside one, since a missed
 * webhook delivery would otherwise leave a paid charge stuck as pending
 * until someone notices and clicks the button by hand.
 */
class SyncPendingPixPaymentsJob implements ShouldQueue
{
    use Queueable;

    public function handle(MercadoPagoService $mercadoPago, ReminderService $reminderService, PixSyncScheduleService $scheduler): void
    {
        if (! $scheduler->isDue()) {
            return;
        }

        Charge::query()
            ->whereIn('status', [ChargeStatus::WaitingPayment, ChargeStatus::Overdue])
            ->whereNotNull('mercado_pago_order_id')
            ->where(fn ($query) => $query->whereNull('pix_expires_at')->orWhere('pix_expires_at', '>', now()))
            ->each(function (Charge $charge) use ($mercadoPago, $reminderService): void {
                try {
                    $result = $mercadoPago->syncChargePayment($charge);

                    if ($result['updated']) {
                        $reminderService->sendPaymentConfirmedReminder($charge->fresh());
                    }
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        Deposit::query()
            ->where('status', DepositStatus::WaitingPayment)
            ->whereNotNull('mercado_pago_order_id')
            ->where(fn ($query) => $query->whereNull('pix_expires_at')->orWhere('pix_expires_at', '>', now()))
            ->each(function (Deposit $deposit) use ($mercadoPago): void {
                try {
                    $mercadoPago->syncDepositPayment($deposit);
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        $scheduler->markRan();
    }
}
