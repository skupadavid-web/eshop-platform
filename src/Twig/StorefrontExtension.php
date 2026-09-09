<?php

declare(strict_types=1);

namespace App\Twig;

use App\Cart\CartService;
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
        private readonly CartService $cart,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('shirt', $this->shirts->svg(...), ['is_safe' => ['html']]),
            new TwigFunction('cart_count', $this->cartCount(...)),
        ];
    }

    public function cartCount(): int
    {
        return $this->storeContext->has() ? $this->cart->count($this->storeContext->get()) : 0;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('money', $this->money(...)),
        ];
    }

    public function money(int|float $amount, ?string $currency = null): string
    {
        $currency ??= $this->storeContext->has() ? $this->storeContext->get()->currency : 'CZK';
        $formatted = number_format((float) $amount, 0, ',', "\u{00a0}");

        return 'EUR' === $currency ? $formatted.' €' : $formatted.' Kč';
    }
}
