<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Subscription\Jobs\ProcessStripeWebhook;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function handleStripe(): JsonResponse
    {
        $payload = request()->all();
        $eventType = data_get($payload, 'type', '');
        $eventId = data_get($payload, 'id', '');

        if (empty($eventId) || empty($eventType)) {
            Log::warning('Stripe webhook: missing event ID or type');

            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $signature = request()->header('Stripe-Signature');

        if ($signature) {
            try {
                Webhook::constructEvent(
                    request()->getContent(),
                    $signature,
                    config('subscription.webhook_secret')
                );
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                Log::warning('Stripe webhook: signature verification failed', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 403);
            } catch (\UnexpectedValueException $e) {
                Log::warning('Stripe webhook: invalid payload', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json(['error' => 'Invalid payload'], 400);
            }
        }

        ProcessStripeWebhook::dispatch($eventId, $eventType, $payload);

        return response()->json(['received' => true], 200);
    }
}
