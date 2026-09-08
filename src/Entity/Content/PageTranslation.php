<?php

declare(strict_types=1);

namespace App\Entity\Content;

use App\Enum\ContentSource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'page_translations')]
#[ORM\UniqueConstraint(name: 'uniq_page_locale', columns: ['page_id', 'locale'])]
class PageTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Page $page;

    #[ORM\Column(length: 8)]
    public string $locale;

    #[ORM\Column(length: 255)]
    public string $title = '';

    #[ORM\Column(length: 255)]
    public string $slug = '';

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

    public function __construct(Page $page, string $locale)
    {
        $this->page = $page;
        $this->locale = $locale;
    }
}
