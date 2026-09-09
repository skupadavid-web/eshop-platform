<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use App\Enum\ContentSource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A product spec row (old 8_shopdata_list). Only genuinely per-product names are
 * stored here ("Určeno jako", "Materiál"…); store-wide constants and category
 * membership are rendered from elsewhere. Feeds the params table + JSON-LD.
 */
#[ORM\Entity]
#[ORM\Table(name: 'product_parameters')]
#[ORM\Index(name: 'idx_param_product', columns: ['product_id'])]
#[ORM\UniqueConstraint(name: 'uniq_param', columns: ['product_id', 'name'])]
class ProductParameter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'parameters')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Product $product;

    #[ORM\Column(length: 60)]
    public string $name;

    #[ORM\Column(length: 255)]
    public string $value;

    #[ORM\Column]
    public int $position = 0;

    #[ORM\Column(enumType: ContentSource::class, options: ['default' => 'template'])]
    public ContentSource $contentSource = ContentSource::Template;

    #[ORM\Column]
    public bool $locked = false;

    public function __construct(Product $product, string $name, string $value)
    {
        $this->product = $product;
        $this->name = $name;
        $this->value = $value;
    }
}
