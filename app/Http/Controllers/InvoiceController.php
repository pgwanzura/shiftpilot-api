<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $invoices = $this->invoiceService->getInvoices($request->all());
        return response()->json([
            'success' => true,
            'data' => $invoices,
            'message' => 'Invoices retrieved successfully'
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['from', 'to', 'payments']);
        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice),
            'message' => 'Invoice retrieved successfully'
        ]);
    }

    public function getPayments(Invoice $invoice): JsonResponse
    {
        $payments = $this->invoiceService->getInvoicePayments($invoice);
        return response()->json([
            'success' => true,
            'data' => $payments,
            'message' => 'Invoice payments retrieved successfully'
        ]);
    }

    public function markAsPaid(Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);
        $invoice = $this->invoiceService->markAsPaid($invoice);

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice),
            'message' => 'Invoice marked as paid successfully'
        ]);
    }

    public function cancel(Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);
        $invoice = $this->invoiceService->cancelInvoice($invoice);

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice),
            'message' => 'Invoice cancelled successfully'
        ]);
    }
}
