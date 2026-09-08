<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** A colour / execution of a product. */
#[ORM\Entity]
#[ORM\Table(name: 'product_variants')]
#[ORM\Index(name: 'idx_variant_legacy', columns: ['legacy_id'])]
class ProductVariant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(nullable: true)]
    public ?int $legacyId = null;

    #[ORM\ManyToOne(inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Product $product;

    #[ORM\Column(length: 60)]
    public string $color = '';            // barva_interni_stupnice / nazev_varianty

    #[ORM\Column(length: 12, nullable: true)]
    public ?string $colorHex = null;

    #[ORM\Column]
    public int $position = 0;

    #[ORM\Column]
    public bool $published = true;

    /** @var Collection<int,VariantTranslation> */
    #[ORM\OneToMany(mappedBy: 'variant', targetEntity: VariantTranslation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $translations;

    /** @var Collection<int,VariantMedia> */
    #[ORM\OneToMany(mappedBy: 'variant', targetEntity: VariantMedia::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public Collection $media;

    /** @var Collection<int,VariantSize> */
    #[ORM\OneToMany(mappedBy: 'variant', targetEntity: VariantSize::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public Collection $sizes;

    public function __construct(Product $product)
    {
        $this->product = $product;
        $this->translations = new ArrayCollection();
        $this->media = new ArrayCollection();
        $this->sizes = new ArrayCollection();
    }
}
