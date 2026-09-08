<?php

declare(strict_types=1);

namespace App\Entity\Customer;

use Doctrine\ORM\Mapping as ORM;

/** GDPR consent record with full history (granted / revoked). */
#[ORM\Entity]
#[ORM\Table(name: 'customer_consents')]
class CustomerConsent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'consents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Customer $customer;

    #[ORM\Column(length: 40)]
    public string $type;                 // marketing | cookies | ...

    #[ORM\Column]
    public \DateTimeImmutable $grantedAt;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(length: 120, nullable: true)]
    public ?string $source = null;

    public function __construct(Customer $customer, string $type)
    {
        $this->customer = $customer;
        $this->type = $type;
        $this->grantedAt = new \DateTimeImmutable();
    }
}
