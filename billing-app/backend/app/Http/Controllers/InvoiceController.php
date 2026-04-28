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
    public function createInvoice(Request $request, $visitId)
    {
        $visit = Visit::where('visit_id', $visitId)
            ->orWhere('id', is_numeric($visitId) ? $visitId : null)
            ->firstOrFail();

        return DB::transaction(function () use ($visit, $request) {
            $invoice = Invoice::create([
                'visit_id' => $visit->id,
                'status'   => 'pending',
                'total_amount' => 0,
                'transaction_ref' => 'ef-' . Str::random(10)
            ]);

            // Ideally items are passed from the clinical module, 
            // but we fall back to empty array if not provided during this prototype phase.
            $items = $request->input('items', []);

            $total = 0;
            foreach ($items as $item) {
                $invoice->items()->create($item);
                $total += $item['total'];
            }

            $invoice->update(['total_amount' => $total]);

            return response()->json($invoice->load('items'), 201);
        });
    }

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
                'method'     => $request->input('method'),
                'status'     => $request->input('method') === 'cash' ? 'confirmed' : 'pending',
                'cashier_id' => 1, // Default for prototype phase
                'transaction_id' => $request->input('method') === 'mobile_money' ? 'EFX-' . strtoupper(Str::random(6)) : null
            ]);

            if ($payment->status === 'confirmed' && ($totalPaid + $payment->amount) >= $invoice->total_amount) {
                $invoice->update(['status' => 'paid']);
            }

            return response()->json(['payment' => $payment]);
        });
    }

    public function getActiveInvoice($visitId)
    {
        // Flexible lookup for prototype testing
        $visit = Visit::where('visit_id', $visitId)
            ->orWhere('id', is_numeric($visitId) ? $visitId : null)
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
        ]);
    }
}
