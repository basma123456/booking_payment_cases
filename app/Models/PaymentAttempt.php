<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\PaymentAttemptStatus;

class PaymentAttempt extends Model
{
    protected $casts = [
        'status' => PaymentAttemptStatus::class,
    ];
}
