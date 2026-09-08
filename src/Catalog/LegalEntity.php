<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * Single source of truth for the seller's identity — rendered in footer, contact,
 * legal pages and Organization structured data. Phase 1: one entity for all stores.
 */
final readonly class LegalEntity
{
    public string $name;
    public string $address;
    public string $ico;
    public bool $vatPayer;
    public string $iban;
    public string $bankAccount;
    public string $samplingRoom;

    public function __construct()
    {
        $this->name = 'David Skupa';
        $this->address = 'Volavkova 1741/1, 162 00 Praha 6';
        $this->ico = '700 77 461';
        $this->vatPayer = false;
        $this->iban = 'CZ5020100000002301596177';
        $this->bankAccount = '2301596177/2010';
        $this->samplingRoom = 'U Elektry 650, Praha 9';
    }
}
