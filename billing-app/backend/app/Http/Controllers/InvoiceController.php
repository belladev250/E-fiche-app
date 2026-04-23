<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Visit;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * POST /api/visits/{visitId}/invoices
     */
    public function createInvoice(Request $request, $visitId)
    {
        $visit = Visit::findOrFail($visitId);

        return DB::transaction(function () use ($visit, $request) {
            $invoice = Invoice::create([
                'visit_id' => $visit->id,
                'status'   => 'pending',
                'total_amount' => 0,
                'transaction_ref' => 'ef-' . Str::random(10)
            ]);

            $items = $request->input('items', [
                ['description' => 'Malaria RDT', 'qty' => 1, 'price' => 5000, 'total' => 5000, 'type' => 'lab'],
                ['description' => 'Consultation', 'qty' => 1, 'price' => 10000, 'total' => 10000, 'type' => 'consultation'],
                ['description' => 'Paracetamol', 'qty' => 2, 'price' => 500, 'total' => 1000, 'type' => 'medication'],
            ]);

            $total = 0;
            foreach ($items as $item) {
                $invoice->items()->create($item);
                $total += $item['total'];
            }

            $invoice->update(['total_amount' => $total]);

            return response()->json($invoice->load('items'), 201);
        });
    }

    /**
     * POST /api/invoices/{invoiceId}/payments
     */
    public function processPayment(Request $request, $invoiceId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:cash,mobile_money'
        ]);

        return DB::transaction(function () use ($request, $invoiceId) {
            $invoice = Invoice::where('id', $invoiceId)
                ->where('status', '!=', 'paid')
                ->lockForUpdate()
                ->firstOrFail();

            $totalPaid = $invoice->payments()->where('status', 'confirmed')->sum('amount');
            $remaining = $invoice->total_amount - $totalPaid;

            if ($request->amount > $remaining) {
                return response()->json(['error' => 'Overpayment not allowed'], 422);
            }

            $payment = $invoice->payments()->create([
                'amount'     => $request->amount,
                'method'     => $request->method,
                'status'     => $request->method === 'cash' ? 'confirmed' : 'pending',
                'cashier_id' => 1,
                'transaction_id' => $request->method === 'mobile_money' ? 'EFX-' . strtoupper(Str::random(6)) : null
            ]);

            if ($payment->status === 'confirmed' && ($totalPaid + $payment->amount) >= $invoice->total_amount) {
                $invoice->update(['status' => 'paid']);
            }

            return response()->json(['payment' => $payment]);
        });
    }

    /**
     * GET /api/visits/{visitId}/active-invoice
     */
    public function getActiveInvoice($visitId)
    {
        $visit = Visit::where('id', $visitId)
            ->orWhere('visit_id', $visitId)
            ->firstOrFail();

        $invoice = $visit->invoices()
            ->where('status', '!=', 'cancelled')
            ->with(['items', 'payments'])
            ->latest()
            ->firstOrFail();

        return response()->json([
            'id' => $invoice->id,
            'patient_name' => $visit->patient_name,
            'visit_id' => $visit->visit_id,
            'status' => $invoice->status,
            'total_amount' => $invoice->total_amount,
            'items' => $invoice->items,
            'payments' => $invoice->payments,
        ])->header('Access-Control-Allow-Origin', '*');
    }
}
