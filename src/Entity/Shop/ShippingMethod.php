<?php

declare(strict_types=1);

namespace App\Entity\Shop;

use App\Enum\ShippingCarrier;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shipping_methods')]
class ShippingMethod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Store $store;

    #[ORM\Column(length: 40)]
    public string $code;

    #[ORM\Column(enumType: ShippingCarrier::class)]
    public ShippingCarrier $carrier;

    /** @var array<string,string> locale => label */
    #[ORM\Column(type: 'json')]
    public array $labels = [];

    #[ORM\Column]
    public int $priceCod = 0;          // dobírka, minor-unit-free int

    #[ORM\Column]
    public int $priceTransfer = 0;     // převodem

    #[ORM\Column]
    public bool $hasPickupPoints = false;

    #[ORM\Column]
    public bool $enabled = true;

    #[ORM\Column]
    public int $position = 0;
}
