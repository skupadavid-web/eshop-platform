<?php

declare(strict_types=1);

namespace App\Store;

/**
 * Immutable description of one storefront (eshop). All request-scoped tenant data
 * resolves from here. Phase 1: catalogue in {@see StoreRegistry}; later table `stores`.
 */
final readonly class Store
{
    public function __construct(
        public string $code,       // tsp | tsl | cd
        public string $name,
        public string $host,       // production hostname
        public string $locale,     // cs | sk
        public string $currency,   // CZK | EUR
        public string $theme,      // visual skin id (phase 1 == code)
        public string $email,
        public string $phone,
        public ?string $partnerCode = null, // linked store for hreflang
    ) {
    }

    public function shortHost(): string
    {
        return explode('.', $this->host)[0];
    }

    public function currencySymbol(): string
    {
        return 'EUR' === $this->currency ? '€' : 'Kč';
    }
}
