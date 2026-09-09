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
        public string $imageBaseUrl = '',   // '' = local /pictures/…; absolute = pull from live site until synced
    ) {
    }

    public function shortHost(): string
    {
        return explode('.', $this->host)[0];
    }

    /**
     * URL for a stored media path. The migrated data is mixed: some paths are
     * already absolute ("https://www…/pictures/x.webp"), others are relative
     * ("pictures/x.webp") and get the configured base (live site until synced).
     */
    public function imageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ('' === $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }
        $path = ltrim($path, '/');

        return ('' !== $this->imageBaseUrl ? rtrim($this->imageBaseUrl, '/') : '').'/'.$path;
    }

    public function currencySymbol(): string
    {
        return 'EUR' === $this->currency ? '€' : 'Kč';
    }
}
