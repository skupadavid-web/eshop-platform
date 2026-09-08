<?php

declare(strict_types=1);

namespace App\Entity\Pricing;

use App\Entity\Catalog\Product;
use App\Entity\Catalog\ProductVariant;
use App\Entity\Shop\Store;
use Doctrine\ORM\Mapping as ORM;

/** Per-store price. Variant price overrides product price when present. */
#[ORM\Entity]
#[ORM\Table(name: 'prices')]
#[ORM\UniqueConstraint(name: 'uniq_price_scope', columns: ['store_id', 'product_id', 'variant_id'])]
class Price
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Store $store;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Product $product;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    public ?ProductVariant $variant = null;

    #[ORM\Column]
    public int $price = 0;               // final customer price (minor-unit-free int)

    #[ORM\Column(nullable: true)]
    public ?int $priceBeforeDiscount = null;

    #[ORM\Column(nullable: true)]
    public ?int $purchasePrice = null;

    #[ORM\Column(length: 8)]
    public string $currency = 'CZK';

    public function __construct(Store $store, Product $product)
    {
        $this->store = $store;
        $this->product = $product;
    }
}
