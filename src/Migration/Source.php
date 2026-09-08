<?php

declare(strict_types=1);

namespace App\Migration;

/** A legacy data source and where it lives in the staging server. */
enum Source: string
{
    case Tsp = 'tsp';
    case Tsl = 'tsl';
    case Cd = 'cd';
    case Central = 'central';

    public function database(): string
    {
        return 'legacy_'.$this->value;
    }

    /** Table prefix in the old "system_4" install. */
    public function prefix(): string
    {
        return match ($this) {
            self::Tsp, self::Tsl => '8_',
            self::Cd => '11_',
            self::Central => '',
        };
    }

    public function storeCode(): string
    {
        return match ($this) {
            self::Tsp => 'tsp',
            self::Tsl => 'tsl',
            self::Cd => 'cd',
            self::Central => throw new \LogicException('central has no single store'),
        };
    }

    public function locale(): string
    {
        return self::Tsl === $this ? 'sk' : 'cs';
    }

    /** old table name, prefixed. */
    public function t(string $name): string
    {
        return $this->database().'.`'.$this->prefix().$name.'`';
    }
}
