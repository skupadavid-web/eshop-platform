<?php

declare(strict_types=1);

namespace App\Store;

/**
 * The catalogue of storefronts. Phase 1 keeps this in code; later it moves to the
 * `stores` table without changing callers.
 */
final class StoreRegistry
{
    /** @var array<string,Store> */
    private array $stores;

    public function __construct()
    {
        $this->stores = [];
        foreach ([
            new Store('tsp', 'Trička s potiskem', 'trickaspotiskem.eu', 'cs', 'CZK', 'tsl'),
            new Store('tsl', 'Tričká s potlačou', 'trickaspotlacou.eu', 'sk', 'EUR', 'tsp'),
            new Store('cd', 'Cool dresy', 'cooldresy.cz', 'cs', 'CZK'),
        ] as $store) {
            $this->stores[$store->code] = $store;
        }
    }

    /** @return list<Store> */
    public function all(): array
    {
        return array_values($this->stores);
    }

    public function get(string $code): Store
    {
        return $this->stores[$code]
            ?? throw new \InvalidArgumentException(sprintf('Unknown store "%s".', $code));
    }

    /**
     * Resolve a storefront from an incoming HTTP host. Handles www prefix, port,
     * and the local *.ddev.site development suffix.
     */
    public function findByHost(string $host): ?Store
    {
        $host = strtolower($host);
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $host = preg_replace('/\.ddev\.site$/', '', $host) ?? $host;

        foreach ($this->stores as $store) {
            if ($host === $store->host || $host === $store->shortHost()) {
                return $store;
            }
        }

        return null;
    }
}
