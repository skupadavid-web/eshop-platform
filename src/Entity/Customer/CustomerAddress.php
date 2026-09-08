<?php

declare(strict_types=1);

namespace App\Entity\Customer;

use App\Entity\Common\Address;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customer_addresses')]
class CustomerAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Customer $customer;

    #[ORM\Column(length: 16, options: ['default' => 'billing'])]
    public string $type = 'billing';    // billing | shipping

    #[ORM\Embedded(class: Address::class)]
    public Address $address;

    #[ORM\Column]
    public bool $isDefault = false;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
        $this->address = new Address();
    }
}
