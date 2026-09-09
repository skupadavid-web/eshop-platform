<?php

declare(strict_types=1);

namespace App\Store;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class StoreRegistry
{
    /** @var array<string,Store> */
    private array $stores;

    /**
     * @param string $imageHostPrefix while product images are not synced locally, set this
     *                                (e.g. "https://www.") to serve them from the live sites;
     *                                empty means local "/pictures/…"
     */
    public function __construct(
        #[Autowire('%env(default::PRODUCT_IMAGE_HOST_PREFIX)%')]
        string $imageHostPrefix = '',
    ) {
        $img = static fn (string $host): string => '' !== $imageHostPrefix ? $imageHostPrefix.$host : '';

        $this->stores = [];
        foreach ([
            new Store('tsp', 'Trička s potiskem', 'trickaspotiskem.eu', 'cs', 'CZK', 'tsp', 'info@trickaspotiskem.eu', '+420 777 240 837', 'tsl', $img('trickaspotiskem.eu')),
            new Store('tsl', 'Tričká s potlačou', 'trickaspotlacou.eu', 'sk', 'EUR', 'tsl', 'info@trickaspotlacou.eu', '+420 777 240 837', 'tsp', $img('trickaspotlacou.eu')),
            new Store('cd', 'Cool dresy', 'cooldresy.cz', 'cs', 'CZK', 'cd', 'info@cooldresy.cz', '+420 777 240 837', null, $img('cooldresy.cz')),
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
