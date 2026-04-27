# eFiche Patient Billing Module

A specialized billing module designed for healthcare facilities in emerging markets. This system handles concurrent payment processing, idempotent webhooks for mobile money (Momo), and insurance-integrated billing workflows.

Key focus areas: **High Availability**, **Data Consistency**, and **Offline-first robustness** for environments with intermittent connectivity.

---

##  Tech Stack

- **Backend**: Laravel 10+ (PHP 8.2)
- **Frontend**: Next.js 14 (App Router)
- **Database**: PostgreSQL 15
- **Infrastructure**: Docker / Laravel Sail

---

##  Getting Started

### Option 1: Docker (Recommended)
We use Laravel Sail for a pre-configured environment.

1.  **Start Services**:
    ```bash
    ./vendor/bin/sail up -d
    ```
2.  **Initialize Database**:
    ```bash
    sail artisan migrate --seed
    ```

### Option 2: Local Setup (Manual)
If you prefer running without Docker:

1.  **Backend**:
    - Install PHP 8.2+ and Composer.
    - Set `DB_CONNECTION=sqlite` in `.env` for quick testing.
    - Run `php artisan migrate --seed` and `php artisan serve`.
2.  **Frontend**:
    - Run `npm install` and `npm run dev`.

---

##  Integration Testing (Webhooks)

The system includes an idempotent webhook handler to prevent duplicate payment processing:

**Endpoint:** `POST /api/webhooks/efichepay`

**Sample Payload:**
```json
{
  "eventId": "evt_unique_12345",
  "status": "PAYMENT_COMPLETE",
  "amount": 5000,
  "orderNumber": "ef-ABC123XYZ", 
  "transactionId": "momo_ref_999"
}
```

---

## 🏗 Key Engineering Decisions

### 1. Pessimistic Locking
In healthcare environments, multiple staff members often view or modify patient records simultaneously. We use `lockForUpdate()` during the payment window to prevent race conditions that could lead to double-payments or corrupted invoice states.

### 2. Idempotent Hook Handling
Mobile money providers often retry webhook notifications if the initial response is delayed. Our `WebhookController` uses a unique constraint enforcement on `eventId` at the database level to ensure that business logic (like updating payment status) is executed **exactly once**, regardless of how many times the provider hits the endpoint.

### 3. Graceful UI State Polling
To handle "rural clinic" network constraints where WebSockets might be unstable, we implemented a robust 3-second polling mechanism with exponential backoff potential. This ensures the UI stays synced with the server-side payment confirmation without requiring a persistent socket connection.

