<?php

declare(strict_types=1);

namespace App\Cart;

final readonly class Cart
{
    /**
     * @param list<CartLine> $lines
     */
    public function __construct(
        public array $lines,
        public string $currency,
    ) {
    }

    public function isEmpty(): bool
    {
        return [] === $this->lines;
    }

    public function itemCount(): int
    {
        return array_sum(array_map(static fn (CartLine $l) => $l->quantity, $this->lines));
    }

    public function itemsTotal(): int
    {
        return array_sum(array_map(static fn (CartLine $l) => $l->lineTotal(), $this->lines));
    }
}
