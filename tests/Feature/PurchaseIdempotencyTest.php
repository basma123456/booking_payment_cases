<?php

namespace Tests\Feature;

use App\Models\Service;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_idempotency_key_returns_same_purchase(): void
    {
        $user = User::factory()->create();
        $service = Service::create([
            'name' => 'Test',
            'price' => 100,
            'capacity' => 1,
            'allocated_count' => 0,
            'available_from' => now()->subMinute(),
            'available_until' => now()->addHour(),
        ]);

        $headers = ['Idempotency-Key' => 'same-key'];

        $first = $this->actingAs($user)->postJson('/api/purchases', [
            'service_id' => $service->id,
        ], $headers);

        $second = $this->actingAs($user)->postJson('/api/purchases', [
            'service_id' => $service->id,
        ], $headers);

        $first->assertCreated();
        $second->assertCreated();
        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id')
        );
    }
}
