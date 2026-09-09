<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use App\Entity\Shop\Store;
use App\Entity\Taxonomy\Category;
use Doctrine\ORM\Mapping as ORM;

/**
 * A spec row that applies to every product of a store (or later, a category) —
 * "Materiál: 100% bavlna", "Gramáž látky: 145 g/m²"… A {@see ProductParameter}
 * of the same name on a product overrides it. Replaces the ~200k duplicate rows
 * the old 8_shopdata_list stored for these near-constant values.
 */
#[ORM\Entity]
#[ORM\Table(name: 'parameter_templates')]
#[ORM\Index(name: 'idx_ptpl_scope', columns: ['store_id', 'category_id'])]
class ParameterTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Store $store;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    public ?Category $category = null;

    #[ORM\Column(length: 60)]
    public string $name;

    #[ORM\Column(length: 255)]
    public string $value;

    #[ORM\Column]
    public int $position = 0;

    public function __construct(Store $store, string $name, string $value)
    {
        $this->store = $store;
        $this->name = $name;
        $this->value = $value;
    }
}
