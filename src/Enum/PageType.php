<?php

declare(strict_types=1);

namespace App\Enum;

enum PageType: string
{
    case Content = 'content';
    case Category = 'category';
    case Landing = 'landing';
    case System = 'system';
}
