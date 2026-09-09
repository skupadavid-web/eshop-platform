<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Catalog\View\CategoryPage;
use App\Catalog\View\ColorRef;
use App\Catalog\View\ContentPage;
use App\Catalog\View\MenuItem;
use App\Catalog\View\PageLink;
use App\Catalog\View\ProductCard;
use App\Catalog\View\ProductPage;
use App\Catalog\View\SizeOption;
use App\Catalog\View\Storefront;
use App\Entity\Catalog\Product;
use App\Entity\Catalog\ProductVariant;
use App\Entity\Content\Page;
use App\Entity\Pricing\Price;
use App\Entity\Shop\Store as StoreEntity;
use App\Entity\Taxonomy\Category;
use App\Entity\Taxonomy\CategoryProduct;
use App\Store\Store;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Turns the migrated catalogue entities into the read-only view objects the
 * storefront templates render. All queries are store- and locale-scoped and
 * paginated — the catalogue is large (tsp ≈ 39k products / 465k variants).
 */
final class Catalog
{
    public const PER_PAGE = 24;
    private const MENU_LIMIT = 24;

    private readonly AsciiSlugger $slugger;

    public function __construct(private readonly EntityManagerInterface $em)
    {
        $this->slugger = new AsciiSlugger('cs');
    }

    public function storefront(Store $store): Storefront
    {
        $storeEntity = $this->storeEntity($store);
        if (!$storeEntity instanceof StoreEntity) {
            return new Storefront([], []);
        }
        $locale = $store->locale;

        /** @var list<int> $topIds */
        $topIds = array_map(static fn (array $r): int => (int) $r['id'], $this->em->createQuery(
            'SELECT c.id AS id FROM '.Category::class.' c
             WHERE c.store = :s AND c.showInMenu = true AND c.published = true
             ORDER BY c.position, c.id'
        )->setParameter('s', $storeEntity)->setMaxResults(self::MENU_LIMIT)->getArrayResult());

        $childrenByParent = $this->childIdsOf($storeEntity, $topIds);
        $allChildIds = array_merge(...array_values($childrenByParent)) ?: [];
        $names = $this->categoryNames($locale, array_merge($topIds, $allChildIds));

        $menu = [];
        foreach ($topIds as $id) {
            if (!isset($names[$id])) {
                continue;
            }
            $kids = [];
            foreach (\array_slice($childrenByParent[$id] ?? [], 0, 14) as $kid) {
                if (isset($names[$kid])) {
                    $kids[] = new MenuItem($kid, $names[$kid]['name'], '/'.$names[$kid]['slug']);
                }
            }
            $menu[] = new MenuItem($id, $names[$id]['name'], '/'.$names[$id]['slug'], $kids, \count($kids) > 6);
        }

        return new Storefront($menu, $this->footerPages($storeEntity, $locale));
    }

    public function category(Store $store, int $categoryId, int $page): ?CategoryPage
    {
        $storeEntity = $this->storeEntity($store);
        $category = $this->em->getRepository(Category::class)->find($categoryId);
        if (!$storeEntity instanceof StoreEntity || !$category instanceof Category || $category->store->id !== $storeEntity->id) {
            return null;
        }
        $locale = $store->locale;
        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        $tr = $this->categoryNames($locale, [(int) $category->id])[(int) $category->id] ?? ['name' => '', 'slug' => ''];
        $meta = $this->em->createQuery(
            'SELECT t.bodyHtml AS body, t.metaDescription AS meta FROM '.\App\Entity\Taxonomy\CategoryTranslation::class.' t
             WHERE t.category = :c AND t.locale = :l'
        )->setParameter('c', $category)->setParameter('l', $locale)->setMaxResults(1)->getOneOrNullResult();

        $total = (int) $this->em->createQuery(
            'SELECT COUNT(cp.id) FROM '.CategoryProduct::class.' cp JOIN cp.product p
             WHERE cp.category = :c AND p.published = true'
        )->setParameter('c', $category)->getSingleScalarResult();

        /** @var list<array{id:int,name:string,subtitle:?string,price:?int}> $rows */
        $rows = $this->em->createQuery(
            'SELECT p.id AS id, pt.name AS name, pt.subtitle AS subtitle, pr.price AS price
             FROM '.CategoryProduct::class.' cp
             JOIN cp.product p
             JOIN '.\App\Entity\Catalog\ProductTranslation::class.' pt ON pt.product = p AND pt.locale = :l
             LEFT JOIN '.Price::class.' pr ON pr.product = p AND pr.store = :s AND pr.variant IS NULL
             WHERE cp.category = :c AND p.published = true
             ORDER BY cp.position, p.id'
        )
            ->setParameter('l', $locale)->setParameter('s', $storeEntity)->setParameter('c', $category)
            ->setFirstResult($offset)->setMaxResults(self::PER_PAGE)->getArrayResult();

        $cards = $this->cards($rows, $locale);

        // breadcrumb: walk up the parent chain
        $crumbs = [];
        $parent = $category->parent;
        $guard = 0;
        while ($parent instanceof Category && $guard++ < 5) {
            $pt = $this->categoryNames($locale, [(int) $parent->id])[(int) $parent->id] ?? null;
            if ($pt) {
                array_unshift($crumbs, ['name' => $pt['name'], 'path' => '/'.$pt['slug']]);
            }
            $parent = $parent->parent;
        }

        // subcategories
        $subs = [];
        $childIds = $this->childIdsOf($storeEntity, [(int) $category->id])[(int) $category->id] ?? [];
        foreach ($this->categoryNames($locale, $childIds) as $cid => $kt) {
            $subs[] = new MenuItem($cid, $kt['name'], '/'.$kt['slug']);
        }

        return new CategoryPage(
            (int) $category->id,
            $tr['name'],
            \is_array($meta) ? ($meta['body'] ?? null) : null,
            \is_array($meta) ? ($meta['meta'] ?? null) : null,
            '/'.$tr['slug'],
            $cards,
            $total,
            $page,
            max(1, (int) ceil($total / self::PER_PAGE)),
            $crumbs,
            $subs,
        );
    }

    public function product(Store $store, int $variantId): ?ProductPage
    {
        $storeEntity = $this->storeEntity($store);
        $variant = $this->em->getRepository(ProductVariant::class)->find($variantId);
        if (!$storeEntity instanceof StoreEntity || !$variant instanceof ProductVariant) {
            return null;
        }
        $product = $variant->product;
        $locale = $store->locale;

        $pt = $this->em->createQuery(
            'SELECT t.name AS name, t.subtitle AS subtitle, t.description AS description
             FROM '.\App\Entity\Catalog\ProductTranslation::class.' t WHERE t.product = :p AND t.locale = :l'
        )->setParameter('p', $product)->setParameter('l', $locale)->setMaxResults(1)->getOneOrNullResult();
        $pt = \is_array($pt) ? $pt : ['name' => 'Produkt', 'subtitle' => null, 'description' => null];

        $colors = $this->colorsOf($product, $locale);
        $selected = null;
        foreach ($colors as $c) {
            if ($c->variantId === (int) $variant->id) {
                $selected = $c;
                break;
            }
        }
        $selected ??= $colors[0] ?? new ColorRef((int) $variant->id, 'default', $variant->color, $variant->colorHex ?? '#cccccc', '/'.($this->variantSlug($variant, $locale) ?? ''));

        $vt = $this->em->createQuery(
            'SELECT t.descriptionExtended AS ext FROM '.\App\Entity\Catalog\VariantTranslation::class.' t
             WHERE t.variant = :v AND t.locale = :l'
        )->setParameter('v', $variant)->setParameter('l', $locale)->setMaxResults(1)->getOneOrNullResult();

        $sizes = [];
        /** @var list<array{id:int,size:string}> $sizeRows */
        $sizeRows = $this->em->createQuery(
            'SELECT vs.id AS id, vs.size AS size FROM '.\App\Entity\Catalog\VariantSize::class.' vs
             WHERE vs.variant = :v ORDER BY vs.position, vs.id'
        )->setParameter('v', $variant)->getArrayResult();
        foreach ($sizeRows as $s) {
            $sizes[] = new SizeOption((int) $s['id'], (string) $s['size'], true);
        }

        $price = $this->priceFor($storeEntity, $product, $variant);

        return new ProductPage(
            (int) $product->id,
            (int) $variant->id,
            (string) $pt['name'],
            $pt['subtitle'] ?? null,
            $pt['description'] ?? null,
            \is_array($vt) ? ($vt['ext'] ?? null) : null,
            $selected->path,
            $selected->path,
            $price['price'],
            $price['before'],
            $selected,
            $colors,
            $sizes,
            $product->manufacturer,
            $product->material,
            $product->weightGsm,
            $product->gender,
            $product->printTechnology,
        );
    }

    /**
     * Old URL forms carried a numeric id: "/{slug}-{id}", "/{slug}-{id}-{variantId}",
     * "/{slug}-detail-{id}[-{variantId}]". Resolve them to the canonical alias path.
     */
    public function legacyUrlPath(Store $store, string $path): ?string
    {
        $p = rtrim($path, '/');

        // "...-detail-123" / "...-detail-123-456"  -> the canonical underscore form
        if (preg_match('#^(.*)-detail-(\d+)(?:-(\d+))?$#', $p, $m)) {
            $under = $m[1].'_detail-'.$m[2].(($m[3] ?? '') !== '' ? '-'.$m[3] : '');
            if (null !== $this->em->getRepository(\App\Entity\Content\UrlAlias::class)->findOneBy([
                'store' => $this->storeEntity($store), 'locale' => $store->locale, 'path' => $under,
            ])) {
                return $under;
            }
        }

        // "...-{id}-{variantId}" -> resolve variant by legacy id
        if (preg_match('#^.*-(\d+)-(\d+)$#', $p, $m)) {
            $slug = $this->variantSlugByLegacyId($store, (int) $m[2]);
            if (null !== $slug) {
                return '/'.$slug;
            }
        }

        // "...-{id}" -> resolve product by legacy id, jump to its first variant
        if (preg_match('#^.*-(\d+)$#', $p, $m)) {
            $slug = $this->firstVariantSlugByProductLegacyId($store, (int) $m[1]);
            if (null !== $slug) {
                return '/'.$slug;
            }
        }

        return null;
    }

    private function variantSlugByLegacyId(Store $store, int $legacyId): ?string
    {
        $newId = $this->em->createQuery(
            'SELECT m.newId FROM '.\App\Entity\Migration\IdMap::class." m
             WHERE m.source = :src AND m.entityType = 'variant' AND m.oldId = :old"
        )->setParameter('src', $store->code)->setParameter('old', (string) $legacyId)
            ->setMaxResults(1)->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR);
        if (null === $newId) {
            return null;
        }
        $slug = $this->em->createQuery(
            'SELECT t.slug FROM '.\App\Entity\Catalog\VariantTranslation::class.' t
             WHERE IDENTITY(t.variant) = :v AND t.locale = :l'
        )->setParameter('v', (int) $newId)->setParameter('l', $store->locale)
            ->setMaxResults(1)->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR);

        return null === $slug || '' === $slug ? null : (string) $slug;
    }

    private function firstVariantSlugByProductLegacyId(Store $store, int $legacyId): ?string
    {
        $newId = $this->em->createQuery(
            'SELECT m.newId FROM '.\App\Entity\Migration\IdMap::class." m
             WHERE m.source = :src AND m.entityType = 'product' AND m.oldId = :old"
        )->setParameter('src', $store->code)->setParameter('old', (string) $legacyId)
            ->setMaxResults(1)->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR);
        if (null === $newId) {
            return null;
        }
        $slug = $this->em->createQuery(
            'SELECT t.slug FROM '.\App\Entity\Catalog\VariantTranslation::class.' t
             JOIN t.variant v
             WHERE IDENTITY(v.product) = :p AND t.locale = :l AND t.slug IS NOT NULL AND t.slug <> :e
             ORDER BY v.position, v.id'
        )->setParameter('p', (int) $newId)->setParameter('l', $store->locale)->setParameter('e', '')
            ->setMaxResults(1)->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR);

        return null === $slug ? null : (string) $slug;
    }

    public function firstVariantId(int $productId): ?int
    {
        $id = $this->em->createQuery(
            'SELECT v.id FROM '.ProductVariant::class.' v WHERE IDENTITY(v.product) = :p ORDER BY v.position, v.id'
        )->setParameter('p', $productId)->setMaxResults(1)
            ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR);

        return null === $id ? null : (int) $id;
    }

    public function contentPage(Store $store, int $pageId): ?ContentPage
    {
        $page = $this->em->getRepository(Page::class)->find($pageId);
        $storeEntity = $this->storeEntity($store);
        if (!$page instanceof Page || !$storeEntity instanceof StoreEntity || $page->store->id !== $storeEntity->id) {
            return null;
        }
        $t = $this->em->createQuery(
            'SELECT t.title AS title, t.slug AS slug, t.bodyHtml AS body, t.metaDescription AS meta
             FROM '.\App\Entity\Content\PageTranslation::class.' t WHERE t.page = :p AND t.locale = :l'
        )->setParameter('p', $page)->setParameter('l', $store->locale)->setMaxResults(1)->getOneOrNullResult();
        if (!\is_array($t)) {
            return null;
        }

        return new ContentPage((string) $t['slug'], (string) $t['title'], $t['body'] ?? null, $t['meta'] ?? null, '/'.$t['slug']);
    }

    /**
     * @return list<ProductCard>
     */
    public function newestProducts(Store $store, int $limit): array
    {
        $storeEntity = $this->storeEntity($store);
        if (!$storeEntity instanceof StoreEntity) {
            return [];
        }
        $locale = $store->locale;

        // products that belong to at least one of this store's categories, newest first
        /** @var list<array{id:int,name:string,subtitle:?string,price:?int}> $rows */
        $rows = $this->em->createQuery(
            'SELECT DISTINCT p.id AS id, pt.name AS name, pt.subtitle AS subtitle, pr.price AS price
             FROM '.CategoryProduct::class.' cp
             JOIN cp.category c
             JOIN cp.product p
             JOIN '.\App\Entity\Catalog\ProductTranslation::class.' pt ON pt.product = p AND pt.locale = :l
             LEFT JOIN '.Price::class.' pr ON pr.product = p AND pr.store = :s AND pr.variant IS NULL
             WHERE c.store = :s AND p.published = true
             ORDER BY p.id DESC'
        )->setParameter('l', $locale)->setParameter('s', $storeEntity)->setMaxResults($limit)->getArrayResult();

        return $this->cards($rows, $locale);
    }

    /**
     * @return array{items: list<ProductCard>, total: int}
     */
    public function search(Store $store, string $query, int $page): array
    {
        $storeEntity = $this->storeEntity($store);
        $query = trim($query);
        if (!$storeEntity instanceof StoreEntity || mb_strlen($query) < 2) {
            return ['items' => [], 'total' => 0];
        }
        $locale = $store->locale;
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $query).'%';
        $offset = (max(1, $page) - 1) * self::PER_PAGE;

        $base = 'FROM '.CategoryProduct::class.' cp JOIN cp.category c JOIN cp.product p
                 JOIN '.\App\Entity\Catalog\ProductTranslation::class.' pt ON pt.product = p AND pt.locale = :l
                 WHERE c.store = :s AND p.published = true AND (pt.name LIKE :q OR pt.subtitle LIKE :q)';

        $total = (int) $this->em->createQuery('SELECT COUNT(DISTINCT p.id) '.$base)
            ->setParameter('l', $locale)->setParameter('s', $storeEntity)->setParameter('q', $like)
            ->getSingleScalarResult();

        /** @var list<array{id:int,name:string,subtitle:?string,price:?int}> $rows */
        $rows = $this->em->createQuery(
            'SELECT DISTINCT p.id AS id, pt.name AS name, pt.subtitle AS subtitle,
             (SELECT pr.price FROM '.Price::class.' pr WHERE pr.product = p AND pr.store = :s AND pr.variant IS NULL) AS price
             '.$base.' ORDER BY p.id DESC'
        )
            ->setParameter('l', $locale)->setParameter('s', $storeEntity)->setParameter('q', $like)
            ->setFirstResult($offset)->setMaxResults(self::PER_PAGE)->getArrayResult();

        return ['items' => $this->cards($rows, $locale), 'total' => $total];
    }

    // ---- internals ---------------------------------------------------------

    /**
     * @param list<array{id:int,name:string,subtitle:?string,price:?int}> $rows
     *
     * @return list<ProductCard>
     */
    private function cards(array $rows, string $locale): array
    {
        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        $colorsByProduct = $this->colorsForProducts($ids, $locale);

        $cards = [];
        foreach ($rows as $r) {
            $pid = (int) $r['id'];
            $colors = $colorsByProduct[$pid] ?? [];
            $cards[] = new ProductCard(
                $pid,
                (string) $r['name'],
                $r['subtitle'] ?? null,
                $this->productPath($pid, $locale, $colorsByProduct),
                (int) ($r['price'] ?? 0),
                $colors,
                $colors[0]->image ?? null,
            );
        }

        return $cards;
    }

    /**
     * @param array<int,list<ColorRef>> $colorsByProduct
     */
    private function productPath(int $productId, string $locale, array $colorsByProduct): string
    {
        $first = $colorsByProduct[$productId][0] ?? null;

        return null !== $first ? $first->path : '#';
    }

    /**
     * @param list<int> $productIds
     *
     * @return array<int,list<ColorRef>>
     */
    private function colorsForProducts(array $productIds, string $locale): array
    {
        if ([] === $productIds) {
            return [];
        }
        /** @var list<array{pid:int,vid:int,name:string,hex:?string,slug:?string}> $rows */
        $rows = $this->em->createQuery(
            'SELECT IDENTITY(v.product) AS pid, v.id AS vid, v.color AS name, v.colorHex AS hex, vt.slug AS slug
             FROM '.ProductVariant::class.' v
             LEFT JOIN '.\App\Entity\Catalog\VariantTranslation::class.' vt ON vt.variant = v AND vt.locale = :l
             WHERE v.product IN (:ids)
             ORDER BY v.position, v.id'
        )->setParameter('l', $locale)->setParameter('ids', $productIds)->getArrayResult();

        $variantIds = array_map(static fn ($r) => (int) $r['vid'], $rows);
        $imgByVariant = [];
        if ([] !== $variantIds) {
            foreach ($this->em->createQuery(
                'SELECT IDENTITY(vm.variant) AS vid, vm.path AS path FROM '.\App\Entity\Catalog\VariantMedia::class.' vm
                 WHERE vm.variant IN (:vids) ORDER BY vm.position, vm.id'
            )->setParameter('vids', $variantIds)->getArrayResult() as $m) {
                $imgByVariant[(int) $m['vid']] ??= (string) $m['path'];
            }
        }

        $out = [];
        foreach ($rows as $r) {
            $pid = (int) $r['pid'];
            $vid = (int) $r['vid'];
            $slug = (string) ($r['slug'] ?? '');
            $out[$pid][] = new ColorRef(
                $vid,
                $this->colorKey((string) $r['name']),
                (string) $r['name'],
                (string) ($r['hex'] ?? '#cccccc'),
                '' !== $slug ? '/'.$slug : '#',
                $imgByVariant[$vid] ?? null,
            );
        }

        return $out;
    }

    /**
     * @return list<ColorRef>
     */
    private function colorsOf(Product $product, string $locale): array
    {
        return $this->colorsForProducts([(int) $product->id], $locale)[(int) $product->id] ?? [];
    }

    private function variantSlug(ProductVariant $variant, string $locale): ?string
    {
        $slug = $this->em->createQuery(
            'SELECT t.slug FROM '.\App\Entity\Catalog\VariantTranslation::class.' t WHERE t.variant = :v AND t.locale = :l'
        )->setParameter('v', $variant)->setParameter('l', $locale)->setMaxResults(1)
            ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR);

        return null === $slug ? null : (string) $slug;
    }

    /**
     * @return array{price:int, before:?int}
     */
    private function priceFor(StoreEntity $store, Product $product, ProductVariant $variant): array
    {
        /** @var list<array{price:int,before:?int,variant:?int}> $rows */
        $rows = $this->em->createQuery(
            'SELECT pr.price AS price, pr.priceBeforeDiscount AS before, IDENTITY(pr.variant) AS variant
             FROM '.Price::class.' pr
             WHERE pr.product = :p AND pr.store = :s AND (pr.variant = :v OR pr.variant IS NULL)'
        )->setParameter('p', $product)->setParameter('s', $store)->setParameter('v', $variant)->getArrayResult();

        $best = null;
        foreach ($rows as $r) {
            if (null !== $r['variant']) {
                return ['price' => (int) $r['price'], 'before' => null !== $r['before'] ? (int) $r['before'] : null];
            }
            $best = $r;
        }
        if (null === $best) {
            return ['price' => 0, 'before' => null];
        }

        return ['price' => (int) $best['price'], 'before' => null !== $best['before'] ? (int) $best['before'] : null];
    }

    /**
     * @param list<int> $parentIds
     *
     * @return array<int,list<int>> parentId => [childId, ...] (menu order)
     */
    private function childIdsOf(StoreEntity $store, array $parentIds): array
    {
        if ([] === $parentIds) {
            return [];
        }
        /** @var list<array{cid:int,pid:int}> $rows */
        $rows = $this->em->createQuery(
            'SELECT c.id AS cid, IDENTITY(c.parent) AS pid FROM '.Category::class.' c
             WHERE c.store = :s AND IDENTITY(c.parent) IN (:ids) AND c.published = true
             ORDER BY c.position, c.id'
        )->setParameter('s', $store)->setParameter('ids', $parentIds)->getArrayResult();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['pid']][] = (int) $r['cid'];
        }

        return $out;
    }

    /**
     * @param list<int> $ids
     *
     * @return array<int,array{name:string,slug:string}>
     */
    private function categoryNames(string $locale, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if ([] === $ids) {
            return [];
        }
        /** @var list<array{cid:int,name:string,slug:string}> $rows */
        $rows = $this->em->createQuery(
            'SELECT IDENTITY(t.category) AS cid, t.name AS name, t.slug AS slug
             FROM '.\App\Entity\Taxonomy\CategoryTranslation::class.' t
             WHERE t.category IN (:ids) AND t.locale = :l'
        )->setParameter('ids', $ids)->setParameter('l', $locale)->getArrayResult();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['cid']] = ['name' => (string) $r['name'], 'slug' => (string) $r['slug']];
        }

        return $out;
    }

    /**
     * @return array<string,PageLink>
     */
    private function footerPages(StoreEntity $store, string $locale): array
    {
        /** @var list<array{slug:string,title:string}> $rows */
        $rows = $this->em->createQuery(
            'SELECT t.slug AS slug, t.title AS title
             FROM '.\App\Entity\Content\PageTranslation::class.' t JOIN t.page p
             WHERE p.store = :s AND p.published = true AND t.locale = :l AND t.slug <> :e
             ORDER BY p.position, p.id'
        )->setParameter('s', $store)->setParameter('l', $locale)->setParameter('e', '')
            ->setMaxResults(14)->getArrayResult();

        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['slug']] = new PageLink((string) $r['slug'], (string) $r['title'], '/'.$r['slug']);
        }

        return $out;
    }

    private function storeEntity(Store $store): ?StoreEntity
    {
        return $this->em->getRepository(StoreEntity::class)->findOneBy(['code' => $store->code]);
    }

    private function colorKey(string $name): string
    {
        return strtolower((string) $this->slugger->slug($name, '_')) ?: 'barva';
    }
}
