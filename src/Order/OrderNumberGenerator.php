<?php

declare(strict_types=1);

namespace App\Order;

use App\Entity\Order\Order;
use App\Entity\Shop\Store as StoreEntity;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Order code: {STORECODE}-{YY}-{NNNNN}, e.g. "TSP-26-00001". Migrated orders use
 * "{STORECODE}-{legacyId}" (no second dash), so the two ranges never collide.
 */
final class OrderNumberGenerator
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function next(StoreEntity $store): string
    {
        $prefix = strtoupper($store->code).'-'.date('y').'-';

        $last = $this->em->createQuery(
            'SELECT o.code FROM '.Order::class.' o
             WHERE o.store = :s AND o.legacyId IS NULL AND o.code LIKE :p
             ORDER BY o.code DESC'
        )->setParameter('s', $store)->setParameter('p', $prefix.'%')->setMaxResults(1)
            ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR);

        $seq = null !== $last ? ((int) substr((string) $last, \strlen($prefix)) + 1) : 1;

        return $prefix.str_pad((string) $seq, 5, '0', \STR_PAD_LEFT);
    }
}
