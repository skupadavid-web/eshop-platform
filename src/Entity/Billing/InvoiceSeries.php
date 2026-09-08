<?php

declare(strict_types=1);

namespace App\Entity\Billing;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_series')]
class InvoiceSeries
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 40, unique: true)]
    public string $code;

    #[ORM\Column(length: 20)]
    public string $prefix = '';

    #[ORM\Column]
    public int $nextNumber = 1;

    #[ORM\Column]
    public bool $resetYearly = true;

    #[ORM\Column]
    public int $currentYear;

    public function __construct(string $code)
    {
        $this->code = $code;
        $this->currentYear = (int) date('Y');
    }
}
