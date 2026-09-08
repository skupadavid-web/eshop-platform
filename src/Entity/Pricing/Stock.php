<?php

declare(strict_types=1);

namespace App\Entity\Pricing;

use App\Entity\Catalog\VariantSize;
use App\Entity\Shop\Store;
use Doctrine\ORM\Mapping as ORM;

/** Stock is held per store and per sellable unit (variant x size). */
#[ORM\Entity]
#[ORM\Table(name: 'stock')]
#[ORM\UniqueConstraint(name: 'uniq_stock', columns: ['store_id', 'variant_size_id'])]
class Stock
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
    public VariantSize $variantSize;

    #[ORM\Column]
    public int $quantity = 0;

    #[ORM\Column]
    public int $reserved = 0;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $restockAt = null;

    public function __construct(Store $store, VariantSize $variantSize)
    {
        $this->store = $store;
        $this->variantSize = $variantSize;
    }

    public function available(): int
    {
        return max(0, $this->quantity - $this->reserved);
    }
}
