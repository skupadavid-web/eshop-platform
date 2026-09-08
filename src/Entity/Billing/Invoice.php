<?php

declare(strict_types=1);

namespace App\Entity\Billing;

use App\Entity\Common\Address;
use App\Entity\Order\Order;
use App\Entity\Shop\LegalEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoices')]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 40, unique: true)]
    public string $number;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    public InvoiceSeries $series;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Order $order;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    public LegalEntity $supplier;

    #[ORM\Embedded(class: Address::class, columnPrefix: 'buyer_')]
    public Address $buyer;

    #[ORM\Column(type: 'date_immutable')]
    public \DateTimeImmutable $issuedOn;

    #[ORM\Column(type: 'date_immutable')]
    public \DateTimeImmutable $taxableOn;

    #[ORM\Column(type: 'date_immutable')]
    public \DateTimeImmutable $dueOn;

    #[ORM\Column]
    public int $total = 0;

    #[ORM\Column(length: 8)]
    public string $currency = 'CZK';

    #[ORM\Column(length: 20, options: ['default' => 'non_vat'])]
    public string $vatMode = 'non_vat';

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $qrPayment = null;    // SPD*1.0 string

    #[ORM\Column(length: 500, nullable: true)]
    public ?string $pdfPath = null;

    #[ORM\Column(length: 20, options: ['default' => 'issued'])]
    public string $status = 'issued';

    public function __construct(string $number, InvoiceSeries $series, Order $order, LegalEntity $supplier)
    {
        $this->number = $number;
        $this->series = $series;
        $this->order = $order;
        $this->supplier = $supplier;
        $this->buyer = new Address();
        $this->issuedOn = new \DateTimeImmutable();
        $this->taxableOn = new \DateTimeImmutable();
        $this->dueOn = new \DateTimeImmutable('+2 days');
    }
}
