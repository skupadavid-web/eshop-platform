<?php

declare(strict_types=1);

namespace App\Catalog\View;

/** Compact product for grids (category listing, home, related). */
final readonly class ProductCard
{
    /**
     * @param list<ColorRef> $colors
     */
    public function __construct(
        public int $productId,
        public string $name,
        public ?string $subtitle,
        public string $path,
        public int $price,
        public array $colors,
        public ?string $image = null,
        public string $motif = 'blank',
        public ?float $rating = null,
        public int $reviews = 0,
    ) {
    }

    public function primaryColor(): ?ColorRef
    {
        return $this->colors[0] ?? null;
    }
}
