<?php

declare(strict_types=1);

namespace App\Entity\Taxonomy;

use App\Entity\Catalog\Product;
use Doctrine\ORM\Mapping as ORM;

/** M:N category <-> product with ordering (old {w}_stranky_zbozi_xy). */
#[ORM\Entity]
#[ORM\Table(name: 'category_products')]
#[ORM\UniqueConstraint(name: 'uniq_category_product', columns: ['category_id', 'product_id'])]
class CategoryProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Category $category;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Product $product;

    #[ORM\Column]
    public int $position = 0;

    public function __construct(Category $category, Product $product)
    {
        $this->category = $category;
        $this->product = $product;
    }
}
