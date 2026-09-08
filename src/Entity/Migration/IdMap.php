<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\ORM\Mapping as ORM;

/** old id -> new id, per source and entity type. Makes migration idempotent. */
#[ORM\Entity]
#[ORM\Table(name: 'id_map')]
#[ORM\UniqueConstraint(name: 'uniq_idmap', columns: ['source', 'entity_type', 'old_id'])]
#[ORM\Index(name: 'idx_idmap_new', columns: ['entity_type', 'new_id'])]
class IdMap
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 60)]
    public string $source;

    #[ORM\Column(length: 60)]
    public string $entityType;

    #[ORM\Column(length: 64)]
    public string $oldId;

    #[ORM\Column]
    public int $newId;

    public function __construct(string $source, string $entityType, string $oldId, int $newId)
    {
        $this->source = $source;
        $this->entityType = $entityType;
        $this->oldId = $oldId;
        $this->newId = $newId;
    }
}
