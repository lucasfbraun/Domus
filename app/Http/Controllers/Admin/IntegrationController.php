<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Receiver;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only status page summarizing whether external integrations
 * (Mercado Pago OAuth app, WAHA WhatsApp gateway, outgoing mail, the daily
 * cron) are configured. Reads config/DB state only; never performs a live
 * connectivity check against any of these services.
 */
class IntegrationController extends Controller
{
    public function index(): Response
    {
        $mailer = config('mail.default');
        $smtpConfigured = $mailer === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.from.address'));

        $appConfigured = filled(config('services.mercadopago.client_id'))
            && filled(config('services.mercadopago.client_secret'));

        return Inertia::render('admin/Integrations', [
            'mercadoPago' => [
                'appConfigured' => $appConfigured,
                'connectedReceiversCount' => Receiver::query()
                    ->whereNotNull('mp_connected_at')
                    ->count(),
                'sandbox' => (bool) config('services.mercadopago.sandbox_connect'),
                'platformTokenConfigured' => filled(config('services.mercadopago.access_token')),
            ],
            'waha' => [
                'connected' => (bool) config('services.waha.url') && (bool) config('services.waha.api_key'),
                'status' => config('services.waha.session', 'default'),
            ],
            'mail' => [
                'configured' => $smtpConfigured || in_array($mailer, ['log', 'array'], true),
                'mailer' => $mailer,
                'from' => config('mail.from.address'),
            ],
            'cron' => [
                'enabled' => true,
                'last_run' => 'Diário 09:00 / 10:00 America/Sao_Paulo',
            ],
        ]);
    }
}
