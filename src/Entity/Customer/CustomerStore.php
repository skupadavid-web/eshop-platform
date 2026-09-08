<?php

declare(strict_types=1);

namespace App\Entity\Customer;

use App\Entity\Shop\Store;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customer_stores')]
#[ORM\UniqueConstraint(name: 'uniq_customer_store', columns: ['customer_id', 'store_id'])]
class CustomerStore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stores')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Customer $customer;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Store $store;

    #[ORM\Column]
    public \DateTimeImmutable $firstSeenAt;

    public function __construct(Customer $customer, Store $store)
    {
        $this->customer = $customer;
        $this->store = $store;
        $this->firstSeenAt = new \DateTimeImmutable();
    }
}
