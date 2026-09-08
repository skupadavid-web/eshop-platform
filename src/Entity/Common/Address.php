<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;

/** Value object — used both for customer address book and as an order snapshot. */
#[ORM\Embeddable]
final class Address
{
    #[ORM\Column(length: 120, nullable: true)]
    public ?string $company = null;

    #[ORM\Column(length: 120, nullable: true)]
    public ?string $firstName = null;

    #[ORM\Column(length: 120, nullable: true)]
    public ?string $lastName = null;

    #[ORM\Column(length: 200, nullable: true)]
    public ?string $street = null;

    #[ORM\Column(length: 120, nullable: true)]
    public ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    public ?string $zip = null;

    #[ORM\Column(length: 2, options: ['default' => 'CZ'])]
    public string $country = 'CZ';

    #[ORM\Column(length: 20, nullable: true)]
    public ?string $phone = null;

    #[ORM\Column(length: 20, nullable: true)]
    public ?string $companyId = null;   // IČO

    #[ORM\Column(length: 20, nullable: true)]
    public ?string $vatId = null;       // DIČ
}
