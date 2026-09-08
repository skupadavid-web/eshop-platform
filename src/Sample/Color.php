<?php

declare(strict_types=1);

namespace App\Sample;

final readonly class Color
{
    public function __construct(
        public string $key,
        public string $nameCs,
        public string $nameSk,
        public string $hex,
    ) {
    }

    public function name(string $locale): string
    {
        return 'sk' === $locale ? $this->nameSk : $this->nameCs;
    }
}
