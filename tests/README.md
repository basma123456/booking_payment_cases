# Limited Availability Service Booking Backend

Laravel backend for:

- limited-capacity services
- temporary reservations
- idempotent purchase creation
- MyFatoorah payments through direct HTTP calls
- webhook signature verification
- duplicate webhook protection
- stale/out-of-order event protection
- expiration and capacity release
- admin purchase listing
- feature tests

## Important

The project uses the existing MySQL schema supplied for the task. Do not create duplicate migrations for `services`, `purchases`, `payment_attempts`, or `payment_events` if those tables already exist in your database.

The `PurchaseStatus` enum is kept exactly as supplied.

## MyFatoorah

This implementation intentionally does NOT install the MyFatoorah PHP SDK.

It uses Laravel HTTP client and MyFatoorah's REST API.

The current MyFatoorah documentation recommends the V3 hosted-payment flow:

- POST `/v3/payments`
- GET `/v3/payments/{paymentId}`
- webhook V2 for server-to-server payment status updates

Configure the correct country base URL. For Egypt the API base URL is:

`https://api-eg.myfatoorah.com`

## .env

Add:

MYFATOORAH_BASE_URL=https://api-eg.myfatoorah.com
MYFATOORAH_API_KEY=your_api_key
MYFATOORAH_WEBHOOK_SECRET=your_webhook_secret
MYFATOORAH_CURRENCY=EGP
MYFATOORAH_PAYMENT_METHOD=CARD
MYFATOORAH_TIMEOUT=30
PURCHASE_TTL_MINUTES=15

## Middleware alias

For Laravel 11/12/13, register the `admin` alias in `bootstrap/app.php`:

```php
use App\Http\Middleware\EnsureAdmin;

->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => EnsureAdmin::class,
    ]);
})
```

The supplied `bootstrap/app.php` is a clean replacement example. If your project already has middleware configuration, merge the alias instead of overwriting your existing setup.

## Routes

POST `/api/purchases`

Header:

`Idempotency-Key: unique-client-request-key`

Body:

```json
{
  "service_id": 1
}
```

POST `/api/purchases/{purchase}/payment`

Body:

```json
{
  "payment_method": "CARD"
}
```

POST `/webhooks/myfatoorah`

Configure this URL in MyFatoorah Webhook Settings and enable webhook signature protection.

## Scheduler

Run:

`php artisan schedule:work`

or configure the server scheduler to run:

`php artisan schedule:run`

The command:

`php artisan purchases:expire`

releases expired reservations.

## Tests

Run:

`php artisan test`

## Concurrency

The important reservation invariant is protected by locking the service row with `SELECT ... FOR UPDATE` before checking/incrementing `allocated_count`.

All capacity changes happen inside the same database transaction as the purchase state change.

## Late successful payment

If a payment succeeds after the reservation expired/cancelled or the capacity is no longer valid, the purchase is moved to:

`payment_succeeded_unavailable`

This prevents the system from confirming an invalid reservation or overselling capacity. A real production system should then execute the business-approved compensation/refund workflow.
