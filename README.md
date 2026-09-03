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



## TO RUN PROJECT
- composer install
- php artisan storage:link
- php artisan key:generate
- php artisan migrate
- php artisan serve





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



## some suggestions

i think we may make another refunds table and refunds_events table in case of the expiration of purchase and the success of payment attempt and also make another job for failure of the refund and make retries and backoffs as :   
 - trail1  :  10:00 pm      failed   ,
 - trails 2 :  10:10 pm  failed ,
 - trail 3 :  10:20 pm succeed  , 
  - and if failed also we make the status of the refund to failed_needs_manual and then send notification to admin  to manual refund 
  
  
  
  also another suggestion we may put rate limiting on the route of purchase to avoid duplicate requests of the same user at the same time
  besides the idempotency that we already put



## IMPORTANT NOTES HERE

i use some ways to prevent problems

- idempotency  in the purchases table and in purchase_attempts and the important one is the idempotency of the payment_events table
which is the combination of the event_id that is got from the my_fatoorah response and the name of the provider
so if we receive a lot of webhooks related to the same attempt so we know that is this attempt is exists before and we update this attempt and purchase only related to this webhook


- also if the same purchase have an attempt and this attempt receives a 
lot of webhooks at diffrent times related to the same attempt so we update the purchase related to the last received webhook with the recent timestamp in the response but 
if the success status comes first then after that failed come to the same attempt so the success status is more important and will change the status of the purchase than the failed one

- each webhook will be represented by a record in the payement_events table to help us for debugging and monitoring the process

- to reduce and prevent racing and concurrency i use DB::transaction   and   lockForUpdate in the model   and idempotency and unique constraints in tables

- the user press buy button so a request is sent then we receive idempotency and check if  this purchase is exists before or no
if no so we will make DB:transaction and check availability of service and if is available  lockForUpdate of the service row and then make new purchase and new payment_attempt and make the status of the attempt to pending and also the purchase to pending status

- when webhook comes we will check idempotency of the event_id that comes form response  and then update     purchase status related to this and make also a new payment_event record in payment_events table and also update the payment_attempt record  columns:             last_event_occurred_at , attempt_no , state_version , status

 
- there is a job that scheduled in the background to check if any purchase exceeds the expiry date and have status pending to change status of it to expired and decrement the allocated spots of the service 
