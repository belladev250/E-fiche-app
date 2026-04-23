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
    /**
     * POST /api/webhooks/efichepay
     * Receives mobile money confirmation events.
     */
    public function handleEfichePayWebhook(Request $request)
    {
        $payload = $request->all();
        $eventId = $payload['eventId'] ?? null;

        if (!$eventId) {
            return response()->json(['error' => 'Missing eventId'], 400);
        }

        try {
            // ATOMIC Step 1: Record the event to guarantee idempotency via DB Unique Index
            $eventRecord = WebhookEvent::create([
                'event_id' => $eventId,
                'payload'  => $payload,
                'status'   => 'received'
            ]);
        } catch (QueryException $e) {
            // Already processed this specific event payload (Postgres error code 23505 is unique violation)
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

            // Find invoice by internal ref
            $invoice = Invoice::where('transaction_ref', $payload['orderNumber'])
                ->lockForUpdate()
                ->first();

            if ($invoice) {
                // Convert cent-based amount if needed (e.g. payload in Cents/RWF)
                $amount = $payload['amount'] / 1; 

                $payment = $invoice->payments()->create([
                    'amount'     => $amount,
                    'method'     => 'mobile_money',
                    'status'     => 'confirmed',
                    'transaction_id' => $payload['transactionId'] ?? null
                ]);

                // Check if invoice is now fully paid
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
