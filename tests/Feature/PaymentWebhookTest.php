<?php

namespace Tests\Feature;

use App\Enums\PurchaseStatus;
use App\Models\PaymentAttempt;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_webhook_confirms_purchase_once(): void
    {
        config(['myfatoorah.webhook_secret' => 'test-secret']);

        $user = User::factory()->create();

        $service = Service::create([
            'name' => 'Test',
            'price' => 100,
            'capacity' => 1,
            'allocated_count' => 1,
            'available_from' => now()->subHour(),
            'available_until' => now()->addHour(),
        ]);

        $purchase = Purchase::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => PurchaseStatus::PENDING,
            'amount' => 100,
            'expires_at' => now()->addMinutes(15),
            'idempotency_key' => 'webhook-key',
        ]);

        $attempt = PaymentAttempt::create([
            'purchase_id' => $purchase->id,
            'attempt_no' => 1,
            'state_version' => 1,
            'provider' => 'myfatoorah',
            'provider_invoice_id' => '123',
            'provider_payment_id' => 'pay-123',
            'status' => 'pending',
        ]);

        $payload = [
            'Event' => [
                'Code' => 1,
                'Name' => 'PAYMENT_STATUS_CHANGED',
                'Reference' => 'event-123',
                'CreationDate' => now()->toISOString(),
            ],
            'Data' => [
                'Invoice' => [
                    'Id' => '123',
                    'Status' => 'PAID',
                    'ExternalIdentifier' => null,
                ],
                'Transaction' => [
                    'Status' => 'SUCCESS',
                    'PaymentId' => 'pay-123',
                ],
            ],
        ];

        $message = 'Invoice.Id=123,Invoice.Status=PAID,Transaction.Status=SUCCESS,Transaction.PaymentId=pay-123,Invoice.ExternalIdentifier=';
        $signature = base64_encode(hash_hmac('sha256', $message, 'test-secret', true));

        $this->postJson('/webhooks/myfatoorah', $payload, [
            'myfatoorah-signature' => $signature,
        ])->assertNoContent();

        $this->assertEquals(PurchaseStatus::CONFIRMED, $purchase->fresh()->status);
        $this->assertEquals(1, $attempt->fresh()->state_version);
    }
}
