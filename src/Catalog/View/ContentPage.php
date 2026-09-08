<?php

declare(strict_types=1);

namespace App\Catalog\View;

final readonly class ContentPage
{
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $bodyHtml,
        public ?string $metaDescription,
        public string $path,
    ) {
    }
}
