<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'variant_media')]
class VariantMedia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'media')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ProductVariant $variant;

    #[ORM\Column(length: 500)]
    public string $path;

    #[ORM\Column(length: 20, options: ['default' => 'image'])]
    public string $type = 'image';

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $alt = null;

    #[ORM\Column]
    public int $position = 0;

    public function __construct(ProductVariant $variant, string $path)
    {
        $this->variant = $variant;
        $this->path = $path;
    }
}
