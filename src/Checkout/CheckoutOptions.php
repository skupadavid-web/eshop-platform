<?php

declare(strict_types=1);

namespace App\Checkout;

use App\Entity\Shop\PaymentMethod;
use App\Entity\Shop\ShippingMethod;
use App\Entity\Shop\Store as StoreEntity;
use App\Enum\PaymentGateway;
use App\Store\Store;
use Doctrine\ORM\EntityManagerInterface;

final class CheckoutOptions
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Shipping cost depends on how the customer pays: cash-on-delivery uses priceCod.
     *
     * @return list<MethodOption>
     */
    public function shipping(Store $store, bool $cashOnDelivery): array
    {
        $storeEntity = $this->storeEntity($store);
        if (!$storeEntity instanceof StoreEntity) {
            return [];
        }
        /** @var list<ShippingMethod> $methods */
        $methods = $this->em->getRepository(ShippingMethod::class)->findBy(
            ['store' => $storeEntity, 'enabled' => true],
            ['position' => 'ASC'],
        );

        return array_map(
            fn (ShippingMethod $m) => new MethodOption(
                $m->code,
                $this->label($m->labels, $store->locale),
                $cashOnDelivery ? $m->priceCod : $m->priceTransfer,
                $m->hasPickupPoints,
            ),
            $methods,
        );
    }

    /**
     * @return list<MethodOption>
     */
    public function payment(Store $store): array
    {
        $storeEntity = $this->storeEntity($store);
        if (!$storeEntity instanceof StoreEntity) {
            return [];
        }
        /** @var list<PaymentMethod> $methods */
        $methods = $this->em->getRepository(PaymentMethod::class)->findBy(
            ['store' => $storeEntity, 'enabled' => true],
            ['position' => 'ASC'],
        );

        return array_map(
            fn (PaymentMethod $m) => new MethodOption(
                $m->code,
                $this->label($m->labels, $store->locale),
                $m->fee,
                false,
                \in_array($m->gateway, [PaymentGateway::CashOnDelivery, PaymentGateway::CashOnPickup], true),
            ),
            $methods,
        );
    }

    public function isCashOnDelivery(Store $store, string $paymentCode): bool
    {
        foreach ($this->payment($store) as $o) {
            if ($o->code === $paymentCode) {
                return $o->isCashOnDelivery;
            }
        }

        return false;
    }

    public function shippingOption(Store $store, string $code, bool $cod): ?MethodOption
    {
        foreach ($this->shipping($store, $cod) as $o) {
            if ($o->code === $code) {
                return $o;
            }
        }

        return null;
    }

    public function paymentOption(Store $store, string $code): ?MethodOption
    {
        foreach ($this->payment($store) as $o) {
            if ($o->code === $code) {
                return $o;
            }
        }

        return null;
    }

    /** @param array<string,string> $labels */
    private function label(array $labels, string $locale): string
    {
        return $labels[$locale] ?? $labels['cs'] ?? reset($labels) ?: '';
    }

    private function storeEntity(Store $store): ?StoreEntity
    {
        return $this->em->getRepository(StoreEntity::class)->findOneBy(['code' => $store->code]);
    }
}
