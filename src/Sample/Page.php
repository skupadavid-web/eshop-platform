<?php

declare(strict_types=1);

namespace App\Sample;

final readonly class Page
{
    public function __construct(
        public string $slug,
        public string $titleCs,
        public string $titleSk,
        public string $bodyHtmlCs,
        public string $bodyHtmlSk,
    ) {
    }

    public function title(string $locale): string
    {
        return 'sk' === $locale ? $this->titleSk : $this->titleCs;
    }

    public function body(string $locale): string
    {
        return 'sk' === $locale ? $this->bodyHtmlSk : $this->bodyHtmlCs;
    }
}
