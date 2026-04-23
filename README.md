# eFiche Patient Billing Module

A robust billing system for healthcare facilities, handling concurrent payments, idempotent webhooks, and per-facility insurance configuration.

## 🚀 How to Run (Docker)

1.  **Clone and Enter:**
    ```bash
    cd billing-app
    ```

2.  **Start Services:**
    We use Laravel Sail (Docker Compose) for the backend and a standard Node container for the frontend.
    ```bash
    ./vendor/bin/sail up -d
    ```

3.  **Database Setup:**
    ```bash
    sail artisan migrate --seed
    ```

4.  **Frontend Setup:**
    ```bash
    cd frontend
    npm install
    npm run dev
    ```
    Visit: `http://localhost:3000/billing/1`

---

## 🧪 Testing the Webhook Manually

To simulate a successful Mobile Money payment confirmation, send a POST request directly to the webhook endpoint.

**Endpoint:** `POST /api/webhooks/efichepay`

**Payload:**
```json
{
  "eventId": "evt_unique_12345",
  "status": "PAYMENT_COMPLETE",
  "amount": 5000,
  "orderNumber": "ef-ABC123XYZ", 
  "transactionId": "momo_ref_999"
}
```

**Testing Idempotency:**
Send the *exact same* payload twice. The first response will be `200 OK` (Processed), and the second will be `200 OK` (Status: `already_processed`), without creating a duplicate payment record in the database.

---

## 🛠 Known Limitations & Shortcuts

1.  **Auth Mocking:** Cashier ID is hardcoded to `1` in the prototypes. In production, this would use `Auth::id()`.
2.  **Polling vs WebSockets:** Used 3-second polling for payment status updates to keep the prototype simple and robust for rural clinic network conditions. Production would ideally use WebSockets/Pusher for lower latency.
3.  **Unique Constraint on Webhook Events:** The prototype assumes `eventId` is globally unique from the provider side. 
4.  **Currency:** Hardcoded to RWF. Multi-currency support was excluded from this version.

---

## 🏗 Key Architectural Decisions

-   **Pessimistic Locking:** Used `lockForUpdate()` on the Invoice row during payment. This is essential for health facilities where multiple cashiers might handle the same patient visit simultaneously.
-   **Atomic Webhook Processing:** By attempting to `create()` the WebhookEvent first and catching the `UniqueConstraintViolationException`, we ensure that two identical webhook requests cannot both trigger the payment logic.
-   **Facility-Specific Context:** Insurance options are fetched dynamically from the DB based on the facility context, preventing one facility's insurance contracts from leaking into another's UI.
