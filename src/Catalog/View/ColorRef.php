<?php

declare(strict_types=1);

namespace App\Catalog\View;

/** One selectable colour of a product (a migrated ProductVariant). */
final readonly class ColorRef
{
    public function __construct(
        public int $variantId,
        public string $key,
        public string $name,
        public string $hex,
        public string $path,
        public ?string $image = null,
    ) {
    }
}
