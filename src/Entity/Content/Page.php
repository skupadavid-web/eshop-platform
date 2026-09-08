<?php

declare(strict_types=1);

namespace App\Entity\Content;

use App\Entity\Shop\Store;
use App\Enum\PageType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pages')]
#[ORM\Index(name: 'idx_page_legacy', columns: ['legacy_id'])]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(nullable: true)]
    public ?int $legacyId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Store $store;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?Page $parent = null;

    #[ORM\Column(enumType: PageType::class, options: ['default' => 'content'])]
    public PageType $type = PageType::Content;

    #[ORM\Column(length: 60, options: ['default' => 'default'])]
    public string $template = 'default';

    #[ORM\Column]
    public int $position = 0;

    #[ORM\Column]
    public bool $showInMenu = false;

    #[ORM\Column]
    public bool $published = true;

    /** @var Collection<int,PageTranslation> */
    #[ORM\OneToMany(mappedBy: 'page', targetEntity: PageTranslation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $translations;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->translations = new ArrayCollection();
    }
}
