<?php

declare(strict_types=1);

namespace App\Migration;

use App\Entity\Migration\IdMap;
use Doctrine\ORM\EntityManagerInterface;

/** old id -> new id, per source and entity type. Makes every step idempotent. */
final class IdMapper
{
    /** @var array<string,array<string,int>> in-memory cache: "source|type" => [oldId => newId] */
    private array $cache = [];

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function get(Source $source, string $type, int|string $oldId): ?int
    {
        $bucket = $this->bucket($source, $type);

        return $this->cache[$bucket][(string) $oldId] ?? null;
    }

    public function set(Source $source, string $type, int|string $oldId, int $newId): void
    {
        $bucket = $this->bucket($source, $type);
        if (isset($this->cache[$bucket][(string) $oldId])) {
            return;
        }
        $this->cache[$bucket][(string) $oldId] = $newId;
        $this->em->persist(new IdMap($source->value, $type, (string) $oldId, $newId));
    }

    public function warmup(Source $source, string $type): void
    {
        $bucket = $this->bucket($source, $type);
        if (isset($this->cache[$bucket])) {
            return;
        }
        $this->cache[$bucket] = [];
        /** @var IdMap $row */
        foreach ($this->em->getRepository(IdMap::class)->findBy(['source' => $source->value, 'entityType' => $type]) as $row) {
            $this->cache[$bucket][$row->oldId] = $row->newId;
        }
    }

    private function bucket(Source $source, string $type): string
    {
        return $source->value.'|'.$type;
    }
}
