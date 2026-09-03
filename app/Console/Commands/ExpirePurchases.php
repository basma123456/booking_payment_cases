<?php

namespace App\Console\Commands;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Console\Command;

class ExpirePurchases extends Command
{
    protected $signature = 'purchases:expire';
    protected $description = 'Release expired pending purchase reservations';

    public function handle(PurchaseService $service): int
    {
        Purchase::query()
            ->where('status', PurchaseStatus::PENDING)
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($purchases) use ($service) {
                foreach ($purchases as $purchase) {
                    $service->expire($purchase);
                }
            });

        return self::SUCCESS;
    }
}
