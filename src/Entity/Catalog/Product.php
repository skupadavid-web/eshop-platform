<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use App\Entity\Common\TimestampsTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** Product master — shared across all stores; per-store publication/price/stock live elsewhere. */
#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\Index(name: 'idx_product_legacy', columns: ['legacy_id'])]
#[ORM\HasLifecycleCallbacks]
class Product
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    /** id in the old system (for traceability / redirects). */
    #[ORM\Column(nullable: true)]
    public ?int $legacyId = null;

    #[ORM\Column(length: 64, nullable: true)]
    public ?string $sku = null;

    #[ORM\Column(length: 32, nullable: true)]
    public ?string $ean = null;

    #[ORM\Column(length: 120, nullable: true)]
    public ?string $manufacturer = null;

    #[ORM\Column(length: 60, nullable: true)]
    public ?string $productType = null;   // typ_zbozi

    #[ORM\Column(length: 60, nullable: true)]
    public ?string $printTechnology = null;

    #[ORM\Column(length: 20, nullable: true)]
    public ?string $gender = null;

    #[ORM\Column(length: 120, nullable: true)]
    public ?string $material = null;

    #[ORM\Column(nullable: true)]
    public ?int $weightGsm = null;        // gramáž

    #[ORM\Column]
    public bool $published = false;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $publishedAt = null;

    /** @var Collection<int,ProductTranslation> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductTranslation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $translations;

    /** @var Collection<int,ProductVariant> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductVariant::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public Collection $variants;

    /** @var Collection<int,ProductAttributeValue> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductAttributeValue::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $attributeValues;

    /** @var Collection<int,ProductParameter> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductParameter::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public Collection $parameters;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->variants = new ArrayCollection();
        $this->attributeValues = new ArrayCollection();
        $this->parameters = new ArrayCollection();
    }
}
