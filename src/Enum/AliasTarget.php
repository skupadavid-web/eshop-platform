<?php

declare(strict_types=1);

namespace App\Enum;

enum AliasTarget: string
{
    case Product = 'product';
    case Variant = 'variant';
    case Category = 'category';
    case Page = 'page';
}
