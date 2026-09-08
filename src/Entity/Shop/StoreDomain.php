<?php

declare(strict_types=1);

namespace App\Entity\Shop;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'store_domains')]
class StoreDomain
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 160, unique: true)]
    public string $host;

    #[ORM\ManyToOne(inversedBy: 'domains')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Store $store;

    /** Non-primary domains 301 to the primary host. */
    #[ORM\Column]
    public bool $redirectToPrimary = true;

    public function __construct(Store $store, string $host)
    {
        $this->store = $store;
        $this->host = $host;
    }
}
