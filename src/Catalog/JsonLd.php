<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Catalog\View\ProductPage;
use App\Store\Store;

/**
 * schema.org structured data for the product detail, mirroring what the old
 * shop emitted: BreadcrumbList + Product (with offers + additionalProperty) +
 * FAQPage. The Organization block is a template partial.
 */
final class JsonLd
{
    /**
     * @return list<string> ready-to-embed JSON strings
     */
    public function forProduct(ProductPage $p, Store $store, string $baseUrl): array
    {
        return array_map(
            static fn (array $b) => (string) json_encode($b, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES),
            $this->blocks($p, $store, $baseUrl),
        );
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function blocks(ProductPage $p, Store $store, string $baseUrl): array
    {
        $url = rtrim($baseUrl, '/').$p->canonicalPath;
        $blocks = [];

        if ([] !== $p->breadcrumbs) {
            $items = [];
            foreach ($p->breadcrumbs as $i => $c) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $c['name'],
                    'item' => rtrim($baseUrl, '/').$c['path'],
                ];
            }
            $items[] = ['@type' => 'ListItem', 'position' => \count($items) + 1, 'name' => $p->name];
            $blocks[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
        }

        $props = [];
        foreach ($p->parameters as $param) {
            if ('Materiál' === $param['name']) {
                continue; // exposed as the top-level "material" field
            }
            $props[] = ['@type' => 'PropertyValue', 'name' => $param['name'], 'value' => $param['value']];
        }

        $product = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $p->name.($p->selectedColor->name ? ' '.$p->selectedColor->name : ''),
            'description' => $p->metaDescription ?? $p->name,
            'brand' => ['@type' => 'Brand', 'name' => $store->name],
            'color' => $p->selectedColor->name,
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => $store->currency,
                'price' => (string) $p->price,
                'availability' => 'https://schema.org/InStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'priceValidUntil' => (new \DateTimeImmutable('+1 year'))->format('Y-m-d'),
            ],
        ];
        if (null !== ($img = $store->imageUrl($p->selectedColor->image))) {
            $product['image'] = $img;
        }
        if (null !== $p->material) {
            $product['material'] = $p->material;
        }
        if (null !== $p->gender) {
            $g = mb_strtolower($p->gender);
            $sg = str_contains($g, 'dám') || str_contains($g, 'žen') ? 'female' : (str_contains($g, 'pán') || str_contains($g, 'muž') ? 'male' : null);
            if (null !== $sg) {
                $product['audience'] = ['@type' => 'PeopleAudience', 'suggestedGender' => $sg];
            }
        }
        if ([] !== $props) {
            $product['additionalProperty'] = $props;
        }
        $blocks[] = $product;

        if ([] !== $p->faqs) {
            $blocks[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(static fn ($f) => [
                    '@type' => 'Question',
                    'name' => $f['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($f['answerHtml']))],
                ], $p->faqs),
            ];
        }

        return $blocks;
    }
}
