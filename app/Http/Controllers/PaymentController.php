<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * POST /webhooks/stripe
     * 
     * Handle Stripe webhook events.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (!$sigHeader) {
            Log::warning('Stripe webhook received without signature');
            return response()->json(['error' => 'No signature'], 400);
        }

        try {
            $result = $this->paymentService->handleWebhook($payload, $sigHeader);
            
            Log::info('Stripe webhook processed', $result);
            
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Stripe webhook failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
