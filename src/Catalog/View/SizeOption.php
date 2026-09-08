<?php

declare(strict_types=1);

namespace App\Catalog\View;

final readonly class SizeOption
{
    public function __construct(
        public int $variantSizeId,
        public string $label,
        public bool $available = true,
    ) {
    }
}
