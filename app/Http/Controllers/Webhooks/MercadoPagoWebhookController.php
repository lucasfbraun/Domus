<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Services\MercadoPagoService;
use App\Services\ReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MercadoPagoWebhookController extends Controller
{
    public function store(
        Request $request,
        MercadoPagoService $mercadoPago,
        ReminderService $reminderService,
    ): JsonResponse {
        $dataId = (string) (
            $request->query('data.id')
            ?? data_get($request->all(), 'data.id', '')
        );

        if (! $mercadoPago->validateWebhookSignature(
            $request->header('x-signature'),
            $request->header('x-request-id'),
            $dataId,
        )) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $result = $mercadoPago->handleWebhookOrder($request->all());

        if (($result['status'] ?? null) === 'processed' && $dataId !== '') {
            $order = $mercadoPago->fetchOrderDetails($dataId);

            if ($order['externalReference']) {
                $charge = Charge::query()
                    ->with(['contract.tenant', 'contract.property', 'contract.receiver'])
                    ->find($order['externalReference']);

                if ($charge) {
                    $reminderService->sendPaymentConfirmedReminder($charge);
                }
            }
        }

        return response()->json($result);
    }
}
