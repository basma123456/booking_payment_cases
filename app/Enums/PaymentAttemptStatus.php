<?php

namespace App\Enums;

enum PaymentAttemptStatus: string
{
case PENDING = 'pending';
case SUCCEEDED = 'succeeded';
case FAILED = 'failed';
}
