<?php

declare(strict_types=1);

namespace App\Entity\Taxonomy;

use App\Entity\Shop\Store;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** Category / landing page. Hierarchy via self-referencing parent (old {w}_stranky.parent). */
#[ORM\Entity]
#[ORM\Table(name: 'categories')]
#[ORM\Index(name: 'idx_category_legacy', columns: ['legacy_id'])]
class Category
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

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    public ?Category $parent = null;

    /** @var Collection<int,Category> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public Collection $children;

    #[ORM\Column]
    public int $position = 0;

    #[ORM\Column]
    public bool $showInMenu = true;

    #[ORM\Column]
    public bool $published = true;

    /** which storefront layout renders the product listing (replaces vypis_zbozi_spec_*). */
    #[ORM\Column(length: 40, options: ['default' => 'grid'])]
    public string $listingLayout = 'grid';

    /** @var Collection<int,CategoryTranslation> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CategoryTranslation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $translations;

    /** @var Collection<int,CategoryProduct> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CategoryProduct::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public Collection $products;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->products = new ArrayCollection();
    }
}
