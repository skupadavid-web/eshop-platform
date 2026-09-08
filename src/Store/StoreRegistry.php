<?php

declare(strict_types=1);

namespace App\Store;

final class StoreRegistry
{
    /** @var array<string,Store> */
    private array $stores;

    public function __construct()
    {
        $this->stores = [];
        foreach ([
            new Store('tsp', 'Trička s potiskem', 'trickaspotiskem.eu', 'cs', 'CZK', 'tsp', 'info@trickaspotiskem.eu', '+420 777 240 837', 'tsl'),
            new Store('tsl', 'Tričká s potlačou', 'trickaspotlacou.eu', 'sk', 'EUR', 'tsl', 'info@trickaspotlacou.eu', '+420 777 240 837', 'tsp'),
            new Store('cd', 'Cool dresy', 'cooldresy.cz', 'cs', 'CZK', 'cd', 'info@cooldresy.cz', '+420 777 240 837'),
        ] as $s) {
            $this->stores[$s->code] = $s;
        }
    }

    /** @return list<Store> */
    public function all(): array
    {
        return array_values($this->stores);
    }

    public function get(string $code): Store
    {
        return $this->stores[$code] ?? throw new \InvalidArgumentException(sprintf('Unknown store "%s".', $code));
    }

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
