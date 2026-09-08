<?php

declare(strict_types=1);

namespace App\Catalog\View;

final readonly class MenuItem
{
    /**
     * @param list<MenuItem> $children
     */
    public function __construct(
        public int $categoryId,
        public string $name,
        public string $path,
        public array $children = [],
        public bool $mega = false,
    ) {
    }
}
