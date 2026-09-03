<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function create(int $userId, int $serviceId, string $idempotencyKey): Purchase
    {
        return DB::transaction(function () use ($userId, $serviceId, $idempotencyKey) {
            $existing = Purchase::where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->user_id !== $userId || $existing->service_id !== $serviceId) {
                    throw ValidationException::withMessages([
                        'Idempotency-Key' => 'This idempotency key belongs to another request.',
                    ]);
                }

                return $existing->load('service', 'paymentAttempts');
            }

            $service = Service::query()->lockForUpdate()->findOrFail($serviceId);
            $now = now();

            if ($now->lt($service->available_from) || $now->gt($service->available_until)) {
                throw ValidationException::withMessages([
                    'service' => 'This service is not currently available.',
                ]);
            }

            $this->releaseExpiredReservations($service);

            if ($service->allocated_count >= $service->capacity) {
                throw ValidationException::withMessages([
                    'service' => 'No availability remains.',
                ]);
            }

            $purchase = Purchase::create([
                'user_id' => $userId,
                'service_id' => $service->id,
                'status' => PurchaseStatus::PENDING,
                'amount' => $service->price,
                'expires_at' => Carbon::now()->addMinutes(config('booking.purchase_ttl_minutes', 15)),
                'idempotency_key' => $idempotencyKey,
            ]);

            $service->increment('allocated_count');

            return $purchase->load('service');
        });
    }

    public function confirm(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            $service = Service::query()->lockForUpdate()->findOrFail($purchase->service_id);
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->status === PurchaseStatus::CONFIRMED) {
                return $purchase;
            }

            if ($purchase->status !== PurchaseStatus::PENDING) {
                if (in_array($purchase->status, [
                    PurchaseStatus::EXPIRED,
                    PurchaseStatus::CANCELLED,
                    PurchaseStatus::PAYMENT_FAILED,
                ], true)) {
                    $purchase->update(['status' => PurchaseStatus::PAYMENT_SUCCEEDED_UNAVAILABLE]);
                }

                return $purchase->fresh();
            }

            if ($purchase->expires_at?->isPast()) {
                $purchase->update(['status' => PurchaseStatus::PAYMENT_SUCCEEDED_UNAVAILABLE]);
                $service->decrement('allocated_count');

                return $purchase->fresh();
            }

            $purchase->update([
                'status' => PurchaseStatus::CONFIRMED,
                'confirmed_at' => now(),
            ]);

            return $purchase->fresh();
        });
    }

    public function expire(Purchase $purchase): bool
    {
        return DB::transaction(function () use ($purchase) {
            $service = Service::query()->lockForUpdate()->findOrFail($purchase->service_id);
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->status !== PurchaseStatus::PENDING || ! $purchase->expires_at?->isPast()) {
                return false;
            }

            $purchase->update([
                'status' => PurchaseStatus::EXPIRED,
            ]);

            if ($service->allocated_count > 0) {
                $service->decrement('allocated_count');
            }

            return true;
        });
    }

    public function cancel(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            $service = Service::query()->lockForUpdate()->findOrFail($purchase->service_id);
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->status === PurchaseStatus::PENDING) {
                $purchase->update([
                    'status' => PurchaseStatus::CANCELLED,
                    'cancelled_at' => now(),
                ]);

                if ($service->allocated_count > 0) {
                    $service->decrement('allocated_count');
                }
            }

            return $purchase->fresh();
        });
    }

    private function releaseExpiredReservations(Service $service): void
    {
        $expired = Purchase::query()
            ->where('service_id', $service->id)
            ->where('status', PurchaseStatus::PENDING)
            ->where('expires_at', '<=', now())
            ->lockForUpdate()
            ->get();

        if ($expired->isEmpty()) {
            return;
        }

        foreach ($expired as $purchase) {
            $purchase->update(['status' => PurchaseStatus::EXPIRED]);
        }

        $service->decrement('allocated_count', $expired->count());
    }
}
