<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use Doctrine\ORM\Mapping as ORM;

/** The sellable unit: a variant in a concrete size. Stock is tracked here per store. */
#[ORM\Entity]
#[ORM\Table(name: 'variant_sizes')]
class VariantSize
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sizes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ProductVariant $variant;

    #[ORM\Column(length: 20)]
    public string $size;                  // XS..XXXL / "6 let"

    #[ORM\Column(length: 64, nullable: true)]
    public ?string $sku = null;

    #[ORM\Column(length: 32, nullable: true)]
    public ?string $ean = null;

    #[ORM\Column]
    public int $position = 0;

    public function __construct(ProductVariant $variant, string $size)
    {
        $this->variant = $variant;
        $this->size = $size;
    }
}
