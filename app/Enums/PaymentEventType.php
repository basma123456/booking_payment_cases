<?php

namespace App\Enums;

enum PaymentEventType: string
{
    case PAYMENT_STATUS_CHANGED = 'PAYMENT_STATUS_CHANGED';
}
