<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'attribute_values')]
#[ORM\UniqueConstraint(name: 'uniq_attr_value', columns: ['attribute_id', 'value'])]
class AttributeValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'values')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Attribute $attribute;

    #[ORM\Column(length: 255)]
    public string $value;

    public function __construct(Attribute $attribute, string $value)
    {
        $this->attribute = $attribute;
        $this->value = $value;
    }
}
