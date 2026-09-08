<?php

declare(strict_types=1);

namespace App\Controller;

use App\Catalog\Catalog;
use App\Catalog\Routing\UrlAliasResolver;
use App\Enum\AliasTarget;
use App\Store\StoreContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    public function __construct(
        private readonly StoreContext $ctx,
        private readonly Catalog $catalog,
        private readonly UrlAliasResolver $aliases,
    ) {
    }

    #[Route('/hledani', name: 'search', priority: 10)]
    public function search(Request $request): Response
    {
        $store = $this->ctx->get();
        $q = trim((string) $request->query->get('q', ''));
        $page = max(1, $request->query->getInt('page', 1));
        $result = $this->catalog->search($store, $q, $page);

        return $this->render('catalog/search.html.twig', [
            'query' => $q,
            'products' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / Catalog::PER_PAGE)),
        ]);
    }

    /**
     * The catch-all that keeps every old e-shop URL alive: resolves the path
     * through the migrated redirects + url_aliases tables.
     */
    #[Route('/{path}', name: 'catalog', requirements: ['path' => '.+'], priority: -50)]
    public function resolve(string $path): Response
    {
        $store = $this->ctx->get();
        $full = '/'.ltrim($path, '/');

        if (null !== ($redirect = $this->aliases->findRedirect($store, $full))) {
            $code = $redirect->code >= 300 && $redirect->code < 400 ? $redirect->code : 301;

            return $this->redirect($this->absolutise($redirect->target), $code);
        }

        // "/…/stranka-N" pagination suffix on category URLs
        $page = 1;
        if (preg_match('#^(.+)/stranka-(\d+)$#', $full, $m)) {
            $full = $m[1];
            $page = (int) $m[2];
        }

        $alias = $this->aliases->findAlias($store, $full);
        if (null === $alias) {
            // old id-bearing URL forms ("/slug-6382", "/slug-6382-14802", "/slug-detail-…")
            $canonical = $this->catalog->legacyUrlPath($store, $full);
            if (null !== $canonical && $canonical !== $full) {
                return $this->redirect($canonical, 301);
            }
            throw $this->createNotFoundException();
        }

        return match ($alias->targetType) {
            AliasTarget::Category => $this->renderCategory($alias->targetId, $page),
            AliasTarget::Variant => $this->renderProduct($alias->targetId),
            AliasTarget::Page => $this->renderPage($alias->targetId),
            AliasTarget::Product => $this->renderProductByProductId($alias->targetId),
        };
    }

    private function renderCategory(int $categoryId, int $page): Response
    {
        $view = $this->catalog->category($this->ctx->get(), $categoryId, $page);
        if (null === $view) {
            throw $this->createNotFoundException();
        }
        if ($page > 1 && $page > $view->pages) {
            throw $this->createNotFoundException();
        }

        return $this->render('catalog/category.html.twig', ['cat' => $view]);
    }

    private function renderProduct(int $variantId): Response
    {
        $view = $this->catalog->product($this->ctx->get(), $variantId);
        if (null === $view) {
            throw $this->createNotFoundException();
        }

        return $this->render('catalog/product.html.twig', ['product' => $view]);
    }

    private function renderProductByProductId(int $productId): Response
    {
        // legacy product-level alias: fall back to the product's first variant
        $store = $this->ctx->get();
        $variantId = $this->catalog->firstVariantId($productId);
        if (null === $variantId) {
            throw $this->createNotFoundException();
        }

        return $this->renderProduct($variantId);
    }

    private function renderPage(int $pageId): Response
    {
        $view = $this->catalog->contentPage($this->ctx->get(), $pageId);
        if (null === $view) {
            throw $this->createNotFoundException();
        }

        return $this->render('page/content.html.twig', ['page' => $view]);
    }

    private function absolutise(string $target): string
    {
        if (str_starts_with($target, 'http://') || str_starts_with($target, 'https://')) {
            return $target;
        }

        return '/'.ltrim($target, '/');
    }
}
