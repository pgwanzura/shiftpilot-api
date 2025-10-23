<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Payment;
use App\Models\Invoice;
use App\Services\PaymentService;
use App\Services\PaymentProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private PaymentProcessingService $paymentProcessingService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->getPayments($request->all());
        return response()->json([
            'success' => true,
            'data' => $payments,
            'message' => 'Payments retrieved successfully'
        ]);
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['invoice']);
        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
            'message' => 'Payment retrieved successfully'
        ]);
    }

    public function createPaymentIntent(Invoice $invoice): JsonResponse
    {
        $this->authorize('pay', $invoice);
        $paymentIntent = $this->paymentProcessingService->createPaymentIntent($invoice);

        return response()->json([
            'success' => true,
            'data' => $paymentIntent,
            'message' => 'Payment intent created successfully'
        ]);
    }

    public function processPayment(Invoice $invoice, ProcessPaymentRequest $request): JsonResponse
    {
        $this->authorize('pay', $invoice);
        $payment = $this->paymentProcessingService->processPayment($invoice, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
            'message' => 'Payment processed successfully'
        ]);
    }

    public function refund(Payment $payment): JsonResponse
    {
        $this->authorize('refund', $payment);
        $refund = $this->paymentService->refundPayment($payment);

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($refund),
            'message' => 'Payment refunded successfully'
        ]);
    }
}
