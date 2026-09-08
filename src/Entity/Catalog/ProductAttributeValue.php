<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_attribute_values')]
#[ORM\UniqueConstraint(name: 'uniq_pav', columns: ['product_id', 'attribute_value_id'])]
class ProductAttributeValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attributeValues')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Product $product;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public AttributeValue $attributeValue;

    public function __construct(Product $product, AttributeValue $attributeValue)
    {
        $this->product = $product;
        $this->attributeValue = $attributeValue;
    }
}
