<?php

namespace App\Domain\Payments\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
