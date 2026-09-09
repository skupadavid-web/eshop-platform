<?php

declare(strict_types=1);

namespace App\Order;

use App\Cart\Cart;
use App\Cart\CartService;
use App\Checkout\CheckoutData;
use App\Checkout\CheckoutOptions;
use App\Entity\Catalog\ProductVariant;
use App\Entity\Catalog\VariantSize;
use App\Entity\Customer\Customer;
use App\Entity\Customer\CustomerConsent;
use App\Entity\Customer\CustomerStore;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Entity\Order\OrderStatusHistory;
use App\Entity\Shop\Store as StoreEntity;
use App\Enum\OrderStatus;
use App\Store\Store;
use Doctrine\ORM\EntityManagerInterface;

final class OrderPlacer
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CartService $cart,
        private readonly CheckoutOptions $options,
        private readonly OrderNumberGenerator $numbers,
        private readonly OrderMailer $mailer,
    ) {
    }

    public function place(Store $store, CheckoutData $data): Order
    {
        $cart = $this->cart->get($store);
        if ($cart->isEmpty()) {
            throw new \DomainException('cart empty');
        }
        $storeEntity = $this->em->getRepository(StoreEntity::class)->findOneBy(['code' => $store->code]);
        if (!$storeEntity instanceof StoreEntity) {
            throw new \DomainException('store not found');
        }

        $cod = $this->options->isCashOnDelivery($store, (string) $data->paymentMethod);
        $ship = $this->options->shippingOption($store, (string) $data->shippingMethod, $cod);
        $pay = $this->options->paymentOption($store, (string) $data->paymentMethod);
        if (null === $ship || null === $pay) {
            throw new \DomainException('invalid method');
        }

        $email = strtolower(trim((string) $data->email));
        $customer = $this->em->getRepository(Customer::class)->findOneBy(['email' => $email]);
        $newCustomer = !$customer instanceof Customer;
        if ($newCustomer) {
            $customer = new Customer($email);
        }
        if (!$this->hasStoreLink($customer, $storeEntity)) {
            $customer->stores->add(new CustomerStore($customer, $storeEntity));
        }
        if ($data->newsletter) {
            $consent = new CustomerConsent($customer, 'marketing');
            $consent->source = 'checkout';
            $customer->consents->add($consent);
        }

        $order = new Order($storeEntity, $this->numbers->next($storeEntity));
        $order->customer = $customer;
        $order->email = mb_substr($email, 0, 190);
        $order->currency = $store->currency;
        $order->customerNote = self::clean($data->note);
        $order->shippingMethodCode = $ship->code;
        $order->paymentMethodCode = $pay->code;
        $order->pickupPoint = $ship->hasPickupPoints ? self::clean($data->pickupPoint) : null;
        $order->source = 'web';

        $b = $order->billingAddress;
        $b->firstName = self::clean($data->firstName);
        $b->lastName = self::clean($data->lastName);
        $b->company = self::clean($data->company);
        $b->companyId = self::clean($data->companyId);
        $b->vatId = self::clean($data->vatId);
        $b->street = self::clean($data->street);
        $b->city = self::clean($data->city);
        $b->zip = self::clean($data->zip);
        $b->country = $data->country;
        $b->phone = self::clean($data->phone);

        $s = $order->shippingAddress;
        if ($data->shipToDifferent) {
            [$fn, $ln] = self::splitName((string) $data->shipName);
            $s->firstName = $fn;
            $s->lastName = $ln;
            $s->street = self::clean($data->shipStreet);
            $s->city = self::clean($data->shipCity);
            $s->zip = self::clean($data->shipZip);
            $s->country = $data->shipCountry ?: $data->country;
        } else {
            $s->firstName = $b->firstName;
            $s->lastName = $b->lastName;
            $s->company = $b->company;
            $s->street = $b->street;
            $s->city = $b->city;
            $s->zip = $b->zip;
            $s->country = $b->country;
            $s->phone = $b->phone;
        }

        $this->addItems($order, $cart);

        $order->itemsTotal = $cart->itemsTotal();
        $order->shippingTotal = $ship->price + $pay->price;
        $order->discountTotal = 0;
        $order->grandTotal = $order->itemsTotal + $order->shippingTotal - $order->discountTotal;

        $order->status = 'card' === $pay->code ? OrderStatus::AwaitingPayment : OrderStatus::New;
        $h = new OrderStatusHistory($order, $order->status);
        $h->changedBy = 'checkout';
        $h->note = 'Objednávka vytvořena na webu';
        $order->statusHistory->add($h);

        $this->em->persist($customer);
        $this->em->persist($order);
        $this->em->flush();

        $this->cart->clear($store);
        $this->mailer->sendConfirmation($order, $store);

        return $order;
    }

    private function addItems(Order $order, Cart $cart): void
    {
        foreach ($cart->lines as $line) {
            $it = new OrderItem($order);
            $it->variantSize = $this->em->getReference(VariantSize::class, $line->variantSizeId);
            $it->variant = $this->em->getReference(ProductVariant::class, $line->variantId);
            $it->product = $this->em->getReference(\App\Entity\Catalog\Product::class, $line->productId);
            $it->nameSnapshot = mb_substr($line->name, 0, 255);
            $it->variantSnapshot = mb_substr(trim($line->colorName.' / '.$line->size, ' /'), 0, 120);
            $it->skuSnapshot = null !== $line->sku ? mb_substr($line->sku, 0, 64) : null;
            $it->quantity = $line->quantity;
            $it->unitPrice = $line->unitPrice;
            $order->items->add($it);
        }
    }

    private function hasStoreLink(Customer $c, StoreEntity $store): bool
    {
        foreach ($c->stores as $cs) {
            if ($cs->store === $store || $cs->store->id === $store->id) {
                return true;
            }
        }

        return false;
    }

    /** @return array{0:?string,1:?string} */
    private static function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        if (\count($parts) < 2) {
            return ['' !== $full ? $full : null, null];
        }
        $ln = array_pop($parts);

        return [implode(' ', $parts), $ln];
    }

    private static function clean(?string $v): ?string
    {
        $v = trim((string) $v);

        return '' === $v ? null : $v;
    }
}
