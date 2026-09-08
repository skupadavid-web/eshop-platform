<?php

declare(strict_types=1);

namespace App\Entity\Shop;

use App\Enum\StoreStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A storefront tenant. One row per e-shop. Replaces the code-based
 * {@see \App\Store\StoreRegistry} once E4 wires the frontend to the database.
 */
#[ORM\Entity]
#[ORM\Table(name: 'stores')]
class Store
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 16, unique: true)]
    public string $code;               // tsp | tsl | cd

    #[ORM\Column(length: 120)]
    public string $name;

    #[ORM\Column(length: 120)]
    public string $primaryHost;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    public array $locales = ['cs'];

    #[ORM\Column(length: 8)]
    public string $defaultLocale = 'cs';

    #[ORM\Column(length: 8)]
    public string $currency = 'CZK';

    #[ORM\Column(length: 40)]
    public string $theme = 'default';

    #[ORM\Column(length: 160)]
    public string $contactEmail = '';

    #[ORM\Column(length: 40)]
    public string $contactPhone = '';

    #[ORM\Column(enumType: StoreStatus::class, options: ['default' => 'active'])]
    public StoreStatus $status = StoreStatus::Active;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    public LegalEntity $legalEntity;

    /** Linked store for cross-domain hreflang (tsp <-> tsl). */
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?Store $partnerStore = null;

    /** @var Collection<int,StoreDomain> */
    #[ORM\OneToMany(mappedBy: 'store', targetEntity: StoreDomain::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $domains;

    public function __construct(string $code, string $name, string $primaryHost, LegalEntity $legalEntity)
    {
        $this->code = $code;
        $this->name = $name;
        $this->primaryHost = $primaryHost;
        $this->legalEntity = $legalEntity;
        $this->domains = new ArrayCollection();
    }
}
