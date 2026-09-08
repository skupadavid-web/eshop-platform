<?php

declare(strict_types=1);

namespace App\Catalog\View;

final readonly class CategoryPage
{
    /**
     * @param list<ProductCard>                    $products
     * @param list<array{name:string,path:string}> $crumbs        ancestors, excluding the category itself
     * @param list<MenuItem>                       $subcategories
     */
    public function __construct(
        public int $categoryId,
        public string $name,
        public ?string $bodyHtml,
        public ?string $metaDescription,
        public string $path,
        public array $products,
        public int $total,
        public int $page,
        public int $pages,
        public array $crumbs = [],
        public array $subcategories = [],
    ) {
    }

    public function hasPrev(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->pages;
    }

    public function pagePath(int $n): string
    {
        return 1 === $n ? $this->path : $this->path.'/stranka-'.$n;
    }
}
