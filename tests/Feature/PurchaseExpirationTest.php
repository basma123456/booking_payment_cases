<?php

namespace Tests\Feature;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiration_releases_reserved_capacity(): void
    {
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
            'expires_at' => now()->subMinute(),
            'idempotency_key' => 'expired-key',
        ]);

        app(PurchaseService::class)->expire($purchase);

        $this->assertEquals(PurchaseStatus::EXPIRED, $purchase->fresh()->status);
        $this->assertEquals(0, $service->fresh()->allocated_count);
        dump('After expire: ' . $service->fresh()->allocated_count);

    }
}
