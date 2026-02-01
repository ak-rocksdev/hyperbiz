<?php

namespace App\Http\Controllers;

use App\Services\Payment\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Handle incoming Stripe webhook.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (!$signature) {
            Log::warning('Stripe webhook received without signature');
            return response()->json(['error' => 'No signature'], 400);
        }

        $result = $this->stripeService->handleWebhook($payload, $signature);

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 400);
        }

        return response()->json(['message' => $result['message']]);
    }
}
