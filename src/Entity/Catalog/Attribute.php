<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** Controlled product attribute (replaces the 3.7M-row EAV table 8_shopdata_list). */
#[ORM\Entity]
#[ORM\Table(name: 'attributes')]
class Attribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 60, unique: true)]
    public string $code;

    #[ORM\Column(length: 120)]
    public string $name;

    #[ORM\Column]
    public bool $filterable = false;

    /** @var Collection<int,AttributeValue> */
    #[ORM\OneToMany(mappedBy: 'attribute', targetEntity: AttributeValue::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $values;

    public function __construct(string $code, string $name)
    {
        $this->code = $code;
        $this->name = $name;
        $this->values = new ArrayCollection();
    }
}
