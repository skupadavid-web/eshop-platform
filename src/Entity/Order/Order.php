<?php

declare(strict_types=1);

namespace App\Entity\Order;

use App\Entity\Common\Address;
use App\Entity\Customer\Customer;
use App\Entity\Shop\Store;
use App\Enum\OrderStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * One orders table for all stores (the old objednavky_all). Per-store views are
 * just a filter; the fulfillment board is the default cross-store view.
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[ORM\Index(name: 'idx_order_legacy', columns: ['store_id', 'legacy_id'])]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(nullable: true)]
    public ?int $legacyId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    public Store $store;

    #[ORM\Column(length: 24, unique: true)]
    public string $code;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?Customer $customer = null;

    #[ORM\Column]
    public \DateTimeImmutable $placedAt;

    #[ORM\Column(enumType: OrderStatus::class, options: ['default' => 'new'])]
    public OrderStatus $status = OrderStatus::New;

    #[ORM\Column(length: 8)]
    public string $currency = 'CZK';

    #[ORM\Column]
    public int $itemsTotal = 0;

    #[ORM\Column]
    public int $shippingTotal = 0;

    #[ORM\Column]
    public int $discountTotal = 0;

    #[ORM\Column]
    public int $grandTotal = 0;

    #[ORM\Column(length: 40, nullable: true)]
    public ?string $shippingMethodCode = null;

    #[ORM\Column(length: 40, nullable: true)]
    public ?string $paymentMethodCode = null;

    #[ORM\Embedded(class: Address::class, columnPrefix: 'billing_')]
    public Address $billingAddress;

    #[ORM\Embedded(class: Address::class, columnPrefix: 'shipping_')]
    public Address $shippingAddress;

    #[ORM\Column(length: 190, nullable: true)]
    public ?string $email = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $customerNote = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $internalNote = null;

    #[ORM\Column(length: 50, nullable: true)]
    public ?string $coupon = null;

    #[ORM\Column(length: 120, nullable: true)]
    public ?string $source = null;       // attribution / zdroj

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $pickupPoint = null;

    #[ORM\Column(length: 60, nullable: true)]
    public ?string $trackingNumber = null;

    #[ORM\Column(length: 40, nullable: true)]
    public ?string $carrier = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $promisedAt = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $shippedAt = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $settledAt = null;

    /** @var Collection<int,OrderItem> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $items;

    /** @var Collection<int,OrderStatusHistory> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderStatusHistory::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['changedAt' => 'ASC'])]
    public Collection $statusHistory;

    public function __construct(Store $store, string $code)
    {
        $this->store = $store;
        $this->code = $code;
        $this->placedAt = new \DateTimeImmutable();
        $this->billingAddress = new Address();
        $this->shippingAddress = new Address();
        $this->items = new ArrayCollection();
        $this->statusHistory = new ArrayCollection();
    }
}
