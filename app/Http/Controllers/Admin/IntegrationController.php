<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationController extends Controller
{
    public function index(): Response
    {
        $mailer = config('mail.default');
        $smtpConfigured = $mailer === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.from.address'));

        return Inertia::render('admin/Integrations', [
            'mercadoPago' => [
                'connected' => filled(config('services.mercadopago.access_token'))
                    || filled(config('services.mercadopago.client_id')),
                'account' => config('services.mercadopago.sandbox_connect') ? 'Sandbox / Orders API' : 'Produção / Orders API',
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
