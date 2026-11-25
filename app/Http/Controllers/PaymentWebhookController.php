<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives "payment succeeded" events from a payment provider.
 *
 * The body is authenticated with an HMAC-SHA256 signature in `X-Signature: sha256=<hex>`.
 * Handling is idempotent, so providers can safely retry deliveries.
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments): JsonResponse
    {
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), (string) config('eventpass.webhook_secret'));

        if (! hash_equals($expected, (string) $request->header('X-Signature'))) {
            return response()->json(['error' => 'invalid signature'], 401);
        }

        $payload = $request->json();

        if ($payload->get('type') !== 'payment.succeeded') {
            return response()->json(['ignored' => true]);
        }

        $order = Order::query()->where('code', (string) $payload->get('order'))->first();

        if (! $order) {
            return response()->json(['error' => 'unknown order'], 404);
        }

        try {
            $paid = $payments->markPaid($order, (string) ($payload->get('reference') ?: 'webhook'));
        } catch (PaymentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }

        return response()->json(['ok' => true, 'status' => $paid->status->value]);
    }
}
