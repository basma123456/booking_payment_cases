<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'status',
        'amount',
        'expires_at',
        'idempotency_key',
        'confirmed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'status' => PurchaseStatus::class,
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function paymentAttempts(): HasMany { return $this->hasMany(PaymentAttempt::class); }
}
