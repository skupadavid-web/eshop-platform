<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Connection;

/** Read-only access to the staging databases (E3). */
final class LegacyDb
{
    public function __construct(private readonly Connection $legacyConnection)
    {
    }

    /**
     * @param list<mixed>|array<string,mixed> $params
     *
     * @return list<array<string,mixed>>
     */
    public function all(string $sql, array $params = []): array
    {
        return $this->legacyConnection->fetchAllAssociative($sql, $params);
    }

    /**
     * @param list<mixed>|array<string,mixed> $params
     *
     * @return array<string,mixed>|null
     */
    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->legacyConnection->fetchAssociative($sql, $params);

        return false === $row ? null : $row;
    }

    /**
     * @param list<mixed>|array<string,mixed> $params
     *
     * @return \Traversable<int,array<string,mixed>>
     */
    public function iterate(string $sql, array $params = []): \Traversable
    {
        return $this->legacyConnection->iterateAssociative($sql, $params);
    }

    public function tableExists(string $database, string $table): bool
    {
        return (bool) $this->legacyConnection->fetchOne(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
            [$database, $table],
        );
    }
}
