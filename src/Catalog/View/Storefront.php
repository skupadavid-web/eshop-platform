<?php

declare(strict_types=1);

namespace App\Catalog\View;

/**
 * Store-wide data every page needs: the category menu and the list of content
 * pages for the footer. Exposed to Twig as {{ view }} (replaces the E1 sample).
 */
final readonly class Storefront
{
    /**
     * @param list<MenuItem>         $menu
     * @param array<string,PageLink> $pages slug => link
     */
    public function __construct(
        public array $menu,
        public array $pages,
    ) {
    }

    public function page(string $slug): ?PageLink
    {
        return $this->pages[$slug] ?? null;
    }

    public function pagePath(string $slug): string
    {
        return $this->pages[$slug]->path ?? '#';
    }
}
