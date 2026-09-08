<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'import_batches')]
class ImportBatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 60)]
    public string $source;               // trickaspotiskem | trickaspotlacou | cooldresy | central

    #[ORM\Column(length: 60)]
    public string $step;

    #[ORM\Column]
    public \DateTimeImmutable $startedAt;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column]
    public int $processed = 0;

    #[ORM\Column]
    public int $skipped = 0;

    /** @var array<string,mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    public ?array $report = null;

    public function __construct(string $source, string $step)
    {
        $this->source = $source;
        $this->step = $step;
        $this->startedAt = new \DateTimeImmutable();
    }
}
