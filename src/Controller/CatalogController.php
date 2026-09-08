<?php

declare(strict_types=1);

namespace App\Controller;

use App\Sample\SampleData;
use App\Store\StoreContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    public function __construct(
        private readonly StoreContext $ctx,
        private readonly SampleData $data,
    ) {
    }

    #[Route('/kategorie/{slug}', name: 'category', requirements: ['slug' => '[a-z0-9-]+'])]
    public function category(string $slug): Response
    {
        $view = $this->data->forStore($this->ctx->get()->code);
        $category = $this->data->findCategory($slug);
        if (null === $category) {
            $t = ucfirst(str_replace('-', ' ', $slug));
            $category = new \App\Sample\Category($slug, $t, $t, 42);
        }
        $parent = $category->parentSlug ? $this->data->findCategory($category->parentSlug) : null;

        // sample grid: repeat store products to fill
        $grid = array_merge($view->products, $view->products);

        return $this->render('catalog/category.html.twig', [
            'view' => $view,
            'category' => $category,
            'parent' => $parent,
            'products' => \array_slice($grid, 0, 9),
        ]);
    }

    #[Route('/produkt/{slug}', name: 'product', requirements: ['slug' => '[a-z0-9-]+'])]
    public function product(string $slug): Response
    {
        $view = $this->data->forStore($this->ctx->get()->code);
        $product = $this->data->findProduct($slug) ?? $view->products[0];
        $category = $this->data->findCategory($product->categorySlug);
        $related = array_values(array_filter($view->products, static fn ($p) => $p->slug !== $product->slug));

        return $this->render('catalog/product.html.twig', [
            'view' => $view,
            'product' => $product,
            'category' => $category,
            'related' => \array_slice($related, 0, 3),
        ]);
    }

    #[Route('/hledani', name: 'search')]
    public function search(): Response
    {
        $view = $this->data->forStore($this->ctx->get()->code);

        return $this->render('catalog/search.html.twig', [
            'view' => $view,
            'query' => 'kočka',
            'products' => \array_slice($view->products, 0, 6),
        ]);
    }
}
