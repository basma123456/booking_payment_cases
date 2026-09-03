<?php

namespace App\Services;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PurchaseStatus;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function createPayment(Purchase $purchase, string $paymentMethod = 'CARD'): array
    {
        $attempt = DB::transaction(function () use ($purchase) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->status !== PurchaseStatus::PENDING) {
                throw new \RuntimeException('Purchase is not payable.');
            }

            $attemptNo = ((int) $purchase->paymentAttempts()->max('attempt_no')) + 1;

            return PaymentAttempt::create([
                'purchase_id' => $purchase->id,
                'attempt_no' => $attemptNo,
                'state_version' => 1,
                'provider' => 'myfatoorah',
                'status' => PaymentAttemptStatus::PENDING,
            ]);
        });

        $response = app(MyFatoorahService::class)->createPayment([
            'PaymentMethod' => $paymentMethod,
            'Order' => [
                'Amount' => (float) $purchase->amount,
                'Currency' => config('myfatoorah.currency', 'EGP'),
                'Reference' => (string) $purchase->id,
            ],
            'Customer' => [
                'Reference' => (string) $purchase->user_id,
                'Name' => $purchase->user?->name ?? 'Customer',
                'Email' => $purchase->user?->email ?? '',
            ],
            'IntegrationUrls' => [
                'Redirection' => route('payments.callback'),
            ],
            'Language' => 'EN',
            'MetaData' => [
                'purchase_id' => $purchase->id,
                'payment_attempt_id' => $attempt->id,
                'idempotency_key' => Str::limit($purchase->idempotency_key, 500, ''),
            ],
        ]);

        $data = $response['Data'] ?? [];

        $attempt->update([
            'provider_invoice_id' => $data['InvoiceId'] ?? null,
            'provider_payment_id' => $data['PaymentId'] ?? null,
        ]);

        return [
            'purchase_id' => $purchase->id,
            'payment_attempt_id' => $attempt->id,
            'invoice_id' => $data['InvoiceId'] ?? null,
            'payment_id' => $data['PaymentId'] ?? null,
            'payment_url' => $data['PaymentURL'] ?? null,
        ];
    }

    public function handleWebhook(array $payload): void
    {
        $eventId = (string) (
            data_get($payload, 'Event.Reference')
            ?? data_get($payload, 'event.reference')
            ?? ''
        );

        if ($eventId === '') {
            throw new \InvalidArgumentException('Webhook event reference is missing.');
        }

        $data = data_get($payload, 'Data', []);
        $invoiceId = data_get($data, 'Invoice.Id');
        $paymentId = data_get($data, 'Transaction.PaymentId');
        $status = strtoupper((string) data_get($data, 'Transaction.Status'));

        $attempt = PaymentAttempt::query()
            ->where('provider', 'myfatoorah')
            ->where(function ($q) use ($invoiceId, $paymentId) {
                $q->where('provider_invoice_id', $invoiceId)
                  ->when($paymentId, fn ($q) => $q->orWhere('provider_payment_id', $paymentId));
            })
            ->first();

        if (! $attempt) {
            return;
        }

        DB::transaction(function () use ($attempt, $payload, $eventId, $status) {
            $attempt = PaymentAttempt::query()->findOrFail($attempt->id);
            $purchase = Purchase::query()->findOrFail($attempt->purchase_id);
            $service = \App\Models\Service::query()->lockForUpdate()->findOrFail($purchase->service_id);
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            $attempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            $occurredAt = data_get($payload, 'Event.CreationDate')
                ? \Illuminate\Support\Carbon::parse(data_get($payload, 'Event.CreationDate'))
                : now();

            $event = PaymentEvent::firstOrCreate(
                [
                    'provider' => 'myfatoorah',
                    'provider_event_id' => $eventId,
                ],
                [
                    'payment_attempt_id' => $attempt->id,
                    'event_type' => data_get($payload, 'Event.Name', 'PAYMENT_STATUS_CHANGED'),
                    'occurred_at' => $occurredAt,
                    'payload' => $payload,
                    'created_at' => now(),
                ]
            );

            if ($event->processed_at) {
                return;
            }

            if ($attempt->last_event_occurred_at &&
                $occurredAt->lt($attempt->last_event_occurred_at)) {
                $event->update(['processed_at' => now()]);
                return;
            }

            $attempt->update([
                'last_event_occurred_at' => $occurredAt,
                'state_version' => $attempt->state_version + 1,
                'status' => $status === 'SUCCESS'
                    ? PaymentAttemptStatus::SUCCESS
                    : PaymentAttemptStatus::FAILED,
            ]);

            if ($status === 'SUCCESS') {
                if ($purchase->status === PurchaseStatus::PENDING &&
                    $purchase->expires_at?->isFuture()) {
                    $purchase->update([
                        'status' => PurchaseStatus::CONFIRMED,
                        'confirmed_at' => now(),
                    ]);
                } elseif ($purchase->status !== PurchaseStatus::CONFIRMED) {
                    $purchase->update([
                        'status' => PurchaseStatus::PAYMENT_SUCCEEDED_UNAVAILABLE,
                    ]);

                    if ($purchase->status === PurchaseStatus::PENDING &&
                        $service->allocated_count > 0) {
                        $service->decrement('allocated_count');
                    }
                }
            } elseif ($purchase->status === PurchaseStatus::PENDING) {
                $purchase->update(['status' => PurchaseStatus::PAYMENT_FAILED]);

                if ($service->allocated_count > 0) {
                    $service->decrement('allocated_count');
                }
            }

            $event->update(['processed_at' => now()]);
        });
    }
}
