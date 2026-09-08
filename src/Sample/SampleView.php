<?php

declare(strict_types=1);

namespace App\Sample;

/** Store-scoped slice of the sample catalogue. */
final readonly class SampleView
{
    /**
     * @param list<Category>      $categories
     * @param list<Product>       $products
     * @param list<Page>          $pages
     * @param array<string,Color> $colors
     */
    public function __construct(
        public array $categories,
        public array $products,
        public array $pages,
        public array $colors,
    ) {
    }

    /** @return list<Category> top-level only */
    public function menu(): array
    {
        return $this->categories;
    }
}
