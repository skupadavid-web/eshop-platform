<?php

declare(strict_types=1);

namespace App\Catalog\Routing;

use App\Entity\Content\Redirect;
use App\Entity\Content\UrlAlias;
use App\Entity\Shop\Store as StoreEntity;
use App\Store\Store;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Resolves an incoming request path against the migrated url_aliases / redirects
 * tables — the backbone that keeps every old e-shop URL alive after the switch.
 */
final class UrlAliasResolver
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function findAlias(Store $store, string $path): ?UrlAlias
    {
        $entity = $this->entity($store);
        if (!$entity instanceof StoreEntity) {
            return null;
        }

        return $this->em->getRepository(UrlAlias::class)->findOneBy([
            'store' => $entity,
            'locale' => $store->locale,
            'path' => $path,
        ]);
    }

    /** A 301 target for this path, if the old site had one. */
    public function findRedirect(Store $store, string $path): ?Redirect
    {
        $entity = $this->entity($store);
        if (!$entity instanceof StoreEntity) {
            return null;
        }

        return $this->em->getRepository(Redirect::class)->findOneBy(['store' => $entity, 'sourcePath' => $path]);
    }

    private function entity(Store $store): ?StoreEntity
    {
        return $this->em->getRepository(StoreEntity::class)->findOneBy(['code' => $store->code]);
    }
}
