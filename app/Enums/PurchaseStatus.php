<?php

namespace App\Enums;

enum PurchaseStatus: string
{
case PENDING = 'pending';
case CONFIRMED = 'confirmed';
case FAILED = 'failed';
case CANCELLED = 'cancelled';
case EXPIRED = 'expired';
case PAYMENT_FAILED = 'payment_failed';
case PAYMENT_SUCCEEDED_UNAVAILABLE = 'payment_succeeded_unavailable';
}
