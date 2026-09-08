<?php

declare(strict_types=1);

namespace App\Entity\Order;

use App\Entity\Catalog\Product;
use App\Entity\Catalog\ProductVariant;
use App\Entity\Catalog\VariantSize;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Order $order;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?Product $product = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?ProductVariant $variant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?VariantSize $variantSize = null;

    /** Snapshot — stays correct even if the product is renamed or deleted. */
    #[ORM\Column(length: 255)]
    public string $nameSnapshot = '';

    #[ORM\Column(length: 120, nullable: true)]
    public ?string $variantSnapshot = null;   // "zelená / M"

    #[ORM\Column(length: 64, nullable: true)]
    public ?string $skuSnapshot = null;

    #[ORM\Column]
    public int $quantity = 1;

    #[ORM\Column]
    public int $unitPrice = 0;

    #[ORM\Column(nullable: true)]
    public ?int $vatRate = null;

    #[ORM\Column]
    public int $commission = 0;               // provize

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function lineTotal(): int
    {
        return $this->unitPrice * $this->quantity;
    }
}
