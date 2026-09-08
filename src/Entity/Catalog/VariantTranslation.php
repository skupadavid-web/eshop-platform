<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use App\Enum\ContentSource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'variant_translations')]
#[ORM\UniqueConstraint(name: 'uniq_variant_locale', columns: ['variant_id', 'locale'])]
class VariantTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ProductVariant $variant;

    #[ORM\Column(length: 8)]
    public string $locale;

    #[ORM\Column(length: 255)]
    public string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $slug = null;          // alias_varianta

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $descriptionExtended = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $metaDescription = null;

    #[ORM\Column(enumType: ContentSource::class, options: ['default' => 'template'])]
    public ContentSource $contentSource = ContentSource::Template;

    #[ORM\Column]
    public bool $locked = false;

    public function __construct(ProductVariant $variant, string $locale)
    {
        $this->variant = $variant;
        $this->locale = $locale;
    }
}
