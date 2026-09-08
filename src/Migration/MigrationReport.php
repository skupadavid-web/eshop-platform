<?php

declare(strict_types=1);

namespace App\Migration;

final class MigrationReport
{
    /** @var array<string,int> */
    public array $counts = [];
    /** @var list<string> */
    public array $warnings = [];

    public function add(string $key, int $n = 1): void
    {
        $this->counts[$key] = ($this->counts[$key] ?? 0) + $n;
    }

    public function warn(string $message): void
    {
        $this->warnings[] = $message;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return ['counts' => $this->counts, 'warnings' => $this->warnings];
    }
}
