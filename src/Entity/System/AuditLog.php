<?php

declare(strict_types=1);

namespace App\Entity\System;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(name: 'idx_audit_entity', columns: ['entity_type', 'entity_id'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 120, nullable: true)]
    public ?string $actor = null;

    #[ORM\Column(length: 40)]
    public string $action;

    #[ORM\Column(length: 120)]
    public string $entityType;

    #[ORM\Column(nullable: true)]
    public ?int $entityId = null;

    /** @var array<string,mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    public ?array $changes = null;

    #[ORM\Column]
    public \DateTimeImmutable $at;

    public function __construct(string $action, string $entityType)
    {
        $this->action = $action;
        $this->entityType = $entityType;
        $this->at = new \DateTimeImmutable();
    }
}
