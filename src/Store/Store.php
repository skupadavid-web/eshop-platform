<?php

declare(strict_types=1);

namespace App\Store;

/**
 * Immutable description of one storefront (eshop) served by the platform.
 * All request-scoped tenant data resolves from here.
 */
final readonly class Store
{
    public function __construct(
        public string $code,      // tsp | tsl | cd
        public string $name,      // human name
        public string $host,      // production hostname, e.g. trickaspotiskem.eu
        public string $locale,    // cs | sk
        public string $currency,  // CZK | EUR
        public ?string $partnerCode = null, // linked store for hreflang (tsp <-> tsl)
    ) {
    }

    public function shortHost(): string
    {
        return explode('.', $this->host)[0];
    }
}
