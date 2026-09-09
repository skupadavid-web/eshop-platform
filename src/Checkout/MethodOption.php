<?php

declare(strict_types=1);

namespace App\Checkout;

final readonly class MethodOption
{
    public function __construct(
        public string $code,
        public string $label,
        public int $price,
        public bool $hasPickupPoints = false,
        public bool $isCashOnDelivery = false,
    ) {
    }
}
