<?php

declare(strict_types=1);

namespace App\Cart;

final readonly class CartLine
{
    public function __construct(
        public int $variantSizeId,
        public int $productId,
        public int $variantId,
        public string $name,
        public string $colorName,
        public string $colorHex,
        public string $size,
        public ?string $sku,
        public string $path,
        public int $unitPrice,
        public int $quantity,
    ) {
    }

    public function lineTotal(): int
    {
        return $this->unitPrice * $this->quantity;
    }
}
