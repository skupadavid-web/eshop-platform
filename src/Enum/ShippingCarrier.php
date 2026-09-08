<?php

declare(strict_types=1);

namespace App\Enum;

enum ShippingCarrier: string
{
    case Packeta = 'packeta';
    case CzechPost = 'czech_post';
    case SlovakPost = 'slovak_post';
    case Ppl = 'ppl';
    case Dpd = 'dpd';
    case Balikovna = 'balikovna';
    case Pickup = 'pickup';
}
