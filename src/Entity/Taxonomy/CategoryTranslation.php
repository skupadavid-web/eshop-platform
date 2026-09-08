<?php

declare(strict_types=1);

namespace App\Entity\Taxonomy;

use App\Enum\ContentSource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'category_translations')]
#[ORM\UniqueConstraint(name: 'uniq_category_locale', columns: ['category_id', 'locale'])]
class CategoryTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Category $category;

    #[ORM\Column(length: 8)]
    public string $locale;

    #[ORM\Column(length: 255)]
    public string $name = '';

    #[ORM\Column(length: 255)]
    public string $slug = '';            // URL alias segment

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $metaTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $metaDescription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $bodyHtml = null;

    #[ORM\Column(enumType: ContentSource::class, options: ['default' => 'template'])]
    public ContentSource $contentSource = ContentSource::Template;

    #[ORM\Column]
    public bool $locked = false;

    public function __construct(Category $category, string $locale)
    {
        $this->category = $category;
        $this->locale = $locale;
    }
}
