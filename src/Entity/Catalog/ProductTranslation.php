<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use App\Enum\ContentSource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_translations')]
#[ORM\UniqueConstraint(name: 'uniq_product_locale', columns: ['product_id', 'locale'])]
class ProductTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Product $product;

    #[ORM\Column(length: 8)]
    public string $locale;

    #[ORM\Column(length: 255)]
    public string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $subtitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $descriptionShort = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $metaKeywords = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $metaDescription = null;

    // --- content governance (see analyza/06_obsah_a_seo) ---
    #[ORM\Column(enumType: ContentSource::class, options: ['default' => 'template'])]
    public ContentSource $contentSource = ContentSource::Template;

    #[ORM\Column]
    public bool $locked = false;

    public function __construct(Product $product, string $locale)
    {
        $this->product = $product;
        $this->locale = $locale;
    }
}
