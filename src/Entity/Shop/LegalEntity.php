<?php

declare(strict_types=1);

namespace App\Entity\Shop;

use App\Entity\Common\TimestampsTrait;
use Doctrine\ORM\Mapping as ORM;

/** Single source of truth for a seller identity (footer, invoices, Organization schema). */
#[ORM\Entity]
#[ORM\Table(name: 'legal_entities')]
#[ORM\HasLifecycleCallbacks]
class LegalEntity
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 160)]
    public string $name;

    #[ORM\Column(length: 255)]
    public string $address;

    #[ORM\Column(length: 20)]
    public string $companyId;          // IČO

    #[ORM\Column(length: 20, nullable: true)]
    public ?string $vatId = null;      // DIČ

    #[ORM\Column]
    public bool $vatPayer = false;

    #[ORM\Column(length: 34, nullable: true)]
    public ?string $iban = null;

    #[ORM\Column(length: 40, nullable: true)]
    public ?string $bankAccount = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $samplingRoom = null;

    public function __construct(string $name, string $address, string $companyId)
    {
        $this->name = $name;
        $this->address = $address;
        $this->companyId = $companyId;
    }
}
