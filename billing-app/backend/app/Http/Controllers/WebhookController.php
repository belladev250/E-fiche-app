<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class WebhookController extends Controller
{
    public function handleEfichePayWebhook(Request $request)
    {
        $payload = $request->all();
        $eventId = $payload['eventId'] ?? null;

        if (!$eventId) {
            return response()->json(['error' => 'Missing eventId'], 400);
        }

        try {
            // Track event to ensure idempotency
            $eventRecord = WebhookEvent::create([
                'event_id' => $eventId,
                'payload'  => $payload,
                'status'   => 'received'
            ]);
        } catch (QueryException $e) {
            // Duplicate event, return success to acknowledge receipt
            if ($e->getCode() === '23505') {
                return response()->json(['status' => 'already_processed'], 200);
            }
            throw $e;
        }

        return DB::transaction(function () use ($payload, $eventRecord) {
            if (($payload['status'] ?? '') !== 'PAYMENT_COMPLETE') {
                $eventRecord->update(['status' => 'ignored']);
                return response()->json(['status' => 'ok']);
            }

            // Lock invoice to prevent race conditions during concurrent payments
            $invoice = Invoice::where('transaction_ref', $payload['orderNumber'])
                ->lockForUpdate()
                ->first();

            if ($invoice) {
                // We assume amount from Momo provider is in the base currency (RWF)
                $amount = $payload['amount']; 

                $payment = $invoice->payments()->create([
                    'amount'     => $amount,
                    'method'     => 'mobile_money',
                    'status'     => 'confirmed',
                    'transaction_id' => $payload['transactionId'] ?? null
                ]);

                // Update invoice status if fully paid
                $totalPaid = $invoice->payments()->where('status', 'confirmed')->sum('amount');
                if ($totalPaid >= $invoice->total_amount) {
                    $invoice->update(['status' => 'paid']);
                }

                $eventRecord->update(['status' => 'processed']);
            }

            return response()->json(['status' => 'ok']);
        });
    }
}
