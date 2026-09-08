<?php

declare(strict_types=1);

namespace App\Enum;

enum StoreStatus: string
{
    case Active = 'active';
    case Hidden = 'hidden';
}
