<?php

namespace App\Models;

use App\Enums\PaymentAttemptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'purchase_id',
        'attempt_no',
        'last_event_occurred_at',
        'state_version',
        'provider',
        'provider_payment_id',
        'provider_invoice_id',
        'status',
    ];

    protected $casts = [
        'status' => PaymentAttemptStatus::class,
        'last_event_occurred_at' => 'datetime',
    ];

    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function events(): HasMany { return $this->hasMany(PaymentEvent::class); }
}
