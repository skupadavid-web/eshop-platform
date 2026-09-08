<?php

declare(strict_types=1);

namespace App\Sample;

final readonly class Product
{
    /**
     * @param list<string>         $colorKeys
     * @param list<string>         $sizes
     * @param list<string>         $soldOutSizes
     * @param list<string>         $storeCodes
     * @param array{cs:int,sk:int} $price
     */
    public function __construct(
        public string $slug,
        public string $nameCs,
        public string $nameSk,
        public string $subtitleCs,
        public string $subtitleSk,
        public string $descriptionCs,
        public string $descriptionSk,
        public string $categorySlug,
        public string $gender,
        public string $material,
        public int $gramaz,
        public string $motif,
        public float $rating,
        public int $reviews,
        public array $price,
        public array $colorKeys,
        public array $sizes,
        public array $soldOutSizes = [],
        public array $storeCodes = [],
    ) {
    }

    public function name(string $locale): string
    {
        return 'sk' === $locale ? $this->nameSk : $this->nameCs;
    }

    public function subtitle(string $locale): string
    {
        return 'sk' === $locale ? $this->subtitleSk : $this->subtitleCs;
    }

    public function description(string $locale): string
    {
        return 'sk' === $locale ? $this->descriptionSk : $this->descriptionCs;
    }

    public function priceFor(string $locale): int
    {
        return 'sk' === $locale ? $this->price['sk'] : $this->price['cs'];
    }
}
