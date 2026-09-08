<?php

declare(strict_types=1);

namespace App\Entity\Customer;

use App\Entity\Common\TimestampsTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customers')]
#[ORM\Index(name: 'idx_customer_legacy', columns: ['legacy_key'])]
#[ORM\HasLifecycleCallbacks]
class Customer
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 190, unique: true)]
    public string $email;

    #[ORM\Column(nullable: true)]
    public ?string $passwordHash = null;

    /** MD5 hash carried over from the old system; upgraded on first successful login. */
    #[ORM\Column(nullable: true)]
    public ?string $legacyMd5 = null;

    #[ORM\Column(length: 60, nullable: true)]
    public ?string $legacyKey = null;   // {eshop}_{old_id}

    #[ORM\Column(length: 8, nullable: true)]
    public ?string $gender = null;

    /** @var Collection<int,CustomerAddress> */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: CustomerAddress::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $addresses;

    /** @var Collection<int,CustomerStore> */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: CustomerStore::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $stores;

    /** @var Collection<int,CustomerConsent> */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: CustomerConsent::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $consents;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->addresses = new ArrayCollection();
        $this->stores = new ArrayCollection();
        $this->consents = new ArrayCollection();
    }
}
