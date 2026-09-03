<?php

namespace App\Jobs;

use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpirePurchaseReservation implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $purchaseId) {}

    public function handle(PurchaseService $service): void
    {
        $purchase = Purchase::find($this->purchaseId);

        if ($purchase) {
            $service->expire($purchase);
        }
    }
}
