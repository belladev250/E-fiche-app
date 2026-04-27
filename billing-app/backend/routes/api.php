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

