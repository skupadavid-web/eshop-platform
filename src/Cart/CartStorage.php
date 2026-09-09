<?php

declare(strict_types=1);

namespace App\Cart;

use App\Store\Store;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The raw basket kept in the session: per store, a map of variant_size id => qty.
 * Namespacing by store keeps a tsp basket out of tsl.
 */
final class CartStorage
{
    private const KEY = 'cart';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /** @return array<int,int> variantSizeId => qty */
    public function all(Store $store): array
    {
        $cart = $this->session()->get(self::KEY, []);
        \assert(\is_array($cart));
        $lines = $cart[$store->code] ?? [];

        return \is_array($lines) ? array_map('intval', $lines) : [];
    }

    public function set(Store $store, int $variantSizeId, int $qty): void
    {
        $cart = $this->session()->get(self::KEY, []);
        \assert(\is_array($cart));
        $lines = \is_array($cart[$store->code] ?? null) ? $cart[$store->code] : [];

        if ($qty > 0) {
            $lines[$variantSizeId] = min($qty, 99);
        } else {
            unset($lines[$variantSizeId]);
        }

        $cart[$store->code] = $lines;
        $this->session()->set(self::KEY, $cart);
    }

    public function add(Store $store, int $variantSizeId, int $qty): void
    {
        $this->set($store, $variantSizeId, ($this->all($store)[$variantSizeId] ?? 0) + $qty);
    }

    public function clear(Store $store): void
    {
        $cart = $this->session()->get(self::KEY, []);
        \assert(\is_array($cart));
        unset($cart[$store->code]);
        $this->session()->set(self::KEY, $cart);
    }

    private function session(): \Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
