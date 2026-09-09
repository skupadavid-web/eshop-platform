<?php

declare(strict_types=1);

namespace App\Cart;

use App\Entity\Catalog\ProductTranslation;
use App\Entity\Catalog\VariantSize;
use App\Entity\Catalog\VariantTranslation;
use App\Entity\Pricing\Price;
use App\Entity\Shop\Store as StoreEntity;
use App\Store\Store;
use Doctrine\ORM\EntityManagerInterface;

final class CartService
{
    public function __construct(
        private readonly CartStorage $storage,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function add(Store $store, int $variantSizeId, int $qty): void
    {
        $this->storage->add($store, $variantSizeId, max(1, $qty));
    }

    public function setQty(Store $store, int $variantSizeId, int $qty): void
    {
        $this->storage->set($store, $variantSizeId, max(0, $qty));
    }

    public function remove(Store $store, int $variantSizeId): void
    {
        $this->storage->set($store, $variantSizeId, 0);
    }

    public function clear(Store $store): void
    {
        $this->storage->clear($store);
    }

    public function count(Store $store): int
    {
        return array_sum($this->storage->all($store));
    }

    public function get(Store $store): Cart
    {
        $qtys = $this->storage->all($store);
        if ([] === $qtys) {
            return new Cart([], $store->currency);
        }
        $storeEntity = $this->em->getRepository(StoreEntity::class)->findOneBy(['code' => $store->code]);
        if (!$storeEntity instanceof StoreEntity) {
            return new Cart([], $store->currency);
        }
        $ids = array_keys($qtys);
        $locale = $store->locale;

        /** @var list<array{vsid:int,size:string,sku:?string,vid:int,color:string,hex:?string,pid:int,name:string,slug:?string}> $rows */
        $rows = $this->em->createQuery(
            'SELECT vs.id AS vsid, vs.size AS size, vs.sku AS sku, v.id AS vid, v.color AS color,
                    v.colorHex AS hex, p.id AS pid, pt.name AS name, vt.slug AS slug
             FROM '.VariantSize::class.' vs
             JOIN vs.variant v
             JOIN v.product p
             JOIN '.ProductTranslation::class.' pt ON pt.product = p AND pt.locale = :l
             LEFT JOIN '.VariantTranslation::class.' vt ON vt.variant = v AND vt.locale = :l
             WHERE vs.id IN (:ids)'
        )->setParameter('l', $locale)->setParameter('ids', $ids)->getArrayResult();

        $pids = array_values(array_unique(array_map(static fn ($r) => (int) $r['pid'], $rows)));
        $priceByProduct = [];
        if ([] !== $pids) {
            foreach ($this->em->createQuery(
                'SELECT IDENTITY(pr.product) AS pid, pr.price AS price FROM '.Price::class.' pr
                 WHERE pr.product IN (:pids) AND pr.store = :s AND pr.variant IS NULL'
            )->setParameter('pids', $pids)->setParameter('s', $storeEntity)->getArrayResult() as $r) {
                $priceByProduct[(int) $r['pid']] = (int) $r['price'];
            }
        }

        $lines = [];
        foreach ($rows as $r) {
            $vsid = (int) $r['vsid'];
            $qty = $qtys[$vsid] ?? 0;
            if ($qty < 1) {
                continue;
            }
            $slug = (string) ($r['slug'] ?? '');
            $lines[] = new CartLine(
                $vsid,
                (int) $r['pid'],
                (int) $r['vid'],
                (string) $r['name'],
                (string) $r['color'],
                (string) ($r['hex'] ?? '#cccccc'),
                (string) $r['size'],
                $r['sku'] ?? null,
                '' !== $slug ? '/'.$slug : '#',
                $priceByProduct[(int) $r['pid']] ?? 0,
                $qty,
            );
        }

        // drop ids that no longer resolve to a product (deleted since added)
        $found = array_map(static fn (CartLine $l) => $l->variantSizeId, $lines);
        foreach (array_diff($ids, $found) as $missing) {
            $this->storage->set($store, (int) $missing, 0);
        }

        return new Cart($lines, $store->currency);
    }
}
