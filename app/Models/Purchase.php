<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\PurchaseStatus;

class Purchase extends Model
{
    protected $casts = [
        'status' => PurchaseStatus::class,
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];
}
