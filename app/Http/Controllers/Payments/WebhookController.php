<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\InvalidWebhookSignature;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentService $payments): JsonResponse
    {
        try {
            $result = $payments->handleWebhook($provider, $request);
        } catch (InvalidWebhookSignature $e) {
            Log::warning('Rejected payment webhook', ['provider' => $provider, 'reason' => $e->getMessage(), 'ip' => $request->ip()]);

            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 400);
        } catch (\JsonException) {
            return response()->json(['success' => false, 'message' => 'Malformed payload.'], 400);
        }

        // 200 for duplicates too, so the provider stops redelivering.
        return response()->json(['success' => true, 'message' => ucfirst($result)]);
    }
}
