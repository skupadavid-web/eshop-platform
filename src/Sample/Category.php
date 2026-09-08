<?php

declare(strict_types=1);

namespace App\Sample;

final readonly class Category
{
    /**
     * @param list<Category> $children
     * @param list<string>   $storeCodes
     */
    public function __construct(
        public string $slug,
        public string $nameCs,
        public string $nameSk,
        public int $count,
        public array $storeCodes = [],
        public array $children = [],
        public bool $mega = false,
        public ?string $parentSlug = null,
    ) {
    }

    public function name(string $locale): string
    {
        return 'sk' === $locale ? $this->nameSk : $this->nameCs;
    }
}
