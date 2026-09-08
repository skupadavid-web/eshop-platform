<?php

declare(strict_types=1);

namespace App\Twig;

use App\Catalog\ShirtRenderer;
use App\Store\StoreContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class StorefrontExtension extends AbstractExtension
{
    public function __construct(
        private readonly ShirtRenderer $shirts,
        private readonly StoreContext $storeContext,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('shirt', $this->shirts->svg(...), ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('money', $this->money(...)),
        ];
    }

    public function money(int|float $amount): string
    {
        $store = $this->storeContext->get();
        $formatted = number_format((float) $amount, 0, ',', "\u{00a0}");

        return 'EUR' === $store->currency ? $formatted.' €' : $formatted.' Kč';
    }
}
