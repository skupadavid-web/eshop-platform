<?php

declare(strict_types=1);

namespace App\Store;

/**
 * Request-scoped holder of the active storefront. Populated by
 * {@see \App\EventSubscriber\StoreResolverSubscriber} on every main request.
 */
final class StoreContext
{
    private ?Store $store = null;

    public function set(Store $store): void
    {
        $this->store = $store;
    }

    public function get(): Store
    {
        return $this->store ?? throw new \LogicException('Store context is not initialized yet.');
    }

    public function has(): bool
    {
        return $this->store instanceof Store;
    }
}
