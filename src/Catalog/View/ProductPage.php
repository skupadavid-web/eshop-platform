<?php

declare(strict_types=1);

namespace App\Catalog\View;

/** Full product detail, centred on the currently selected variant (colour). */
final readonly class ProductPage
{
    /**
     * @param list<ColorRef>                                 $colors
     * @param list<SizeOption>                               $sizes
     * @param list<array{name:string,value:string}>          $parameters
     * @param list<array{question:string,answerHtml:string}> $faqs
     * @param list<array{name:string,path:string}>           $breadcrumbs
     */
    public function __construct(
        public int $productId,
        public int $variantId,
        public string $name,
        public ?string $subtitle,
        public ?string $descriptionHtml,
        public ?string $descriptionExtendedHtml,
        public string $path,
        public string $canonicalPath,
        public int $price,
        public ?int $priceBeforeDiscount,
        public ColorRef $selectedColor,
        public array $colors,
        public array $sizes,
        public array $parameters = [],
        public array $faqs = [],
        public array $breadcrumbs = [],
        public ?string $metaDescription = null,
        public ?string $manufacturer = null,
        public ?string $material = null,
        public ?int $weightGsm = null,
        public ?string $gender = null,
        public ?string $printTechnology = null,
        public ?float $rating = null,
        public int $reviews = 0,
    ) {
    }

    public function motif(): string
    {
        return 'blank';
    }
}
