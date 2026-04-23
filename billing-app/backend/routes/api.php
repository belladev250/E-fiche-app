<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Invoices and Payments
Route::get('/visits', function() {
    return \App\Models\Visit::latest()->get();
});
Route::post('/visits/{visitId}/invoices', [InvoiceController::class, 'createInvoice']);
Route::get('/visits/{visitId}/active-invoice', [InvoiceController::class, 'getActiveInvoice']);
Route::post('/invoices/{invoiceId}/payments', [InvoiceController::class, 'processPayment']);

// Webhooks
Route::post('/webhooks/efichepay', [WebhookController::class, 'handleEfichePayWebhook']);

// Auxiliary (for frontend polling)
Route::get('/invoices/{invoiceId}', function ($invoiceId) {
    return \App\Models\Invoice::with(['items', 'payments'])
        ->findOrFail($invoiceId);
});

// Mock data route for frontend design consistency
Route::get('/invoices/mock-data', function () {
    return [
        'id' => 'inv-123',
        'patient_name' => 'Uwese Alise',
        'visit_id' => 'VIS-001234',
        'status' => 'pending',
        'total_amount' => 16000,
        'items' => [
            ['id' => '1', 'description' => 'Malaria RDT', 'qty' => 1, 'price' => 5000, 'total' => 5000],
            ['id' => '2', 'description' => 'Consultation', 'qty' => 1, 'price' => 10000, 'total' => 10000],
            ['id' => '3', 'description' => 'Paracetamol', 'qty' => 2, 'price' => 500, 'total' => 1000],
        ],
        'payments' => [
            // Simulating a pending payment if needed for the design check
            // ['id' => 'p1', 'amount' => 16000, 'method' => 'mobile_money', 'status' => 'pending', 'transaction_id' => 'EFX-123-ABC']
        ]
    ];
});
