<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Entity\Catalog\VariantTranslation;
use App\Entity\Content\PageTranslation;
use App\Entity\Content\UrlAlias;
use App\Entity\Migration\IdMap;
use App\Entity\Shop\Store;
use App\Entity\Taxonomy\CategoryTranslation;
use App\Enum\AliasTarget;
use App\Migration\MigrationReport;
use App\Migration\Source;
use Doctrine\ORM\EntityManagerInterface;

/** Build the url_aliases table from migrated slugs — the backbone of URL preservation. */
final class UrlAliasStep implements MigrationStep
{
    private const BATCH = 5000;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function name(): string
    {
        return 'url-aliases';
    }

    public function supports(): array
    {
        return [Source::Tsp, Source::Tsl, Source::Cd];
    }

    public function run(Source $source, MigrationReport $report, bool $dryRun): void
    {
        $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $source->storeCode()]);
        if (!$store instanceof Store) {
            return;
        }
        $storeId = (int) $store->id;
        $locale = $source->locale();

        if (!$dryRun) {
            $this->em->createQuery('DELETE FROM '.UrlAlias::class.' a WHERE a.store = :s')
                ->setParameter('s', $store)->execute();
        }

        // collect every (path, target) pair up front — scalar hydration, no entities in the UoW
        /** @var list<array{0:string,1:AliasTarget,2:int}> $pending */
        $pending = [];
        /** @var array<string,true> $seen */
        $seen = [];
        $push = static function (string $path, AliasTarget $type, int $id) use (&$pending, &$seen, $report): void {
            $path = '/'.ltrim($path, '/');
            if ('' === trim($path, '/') || mb_strlen($path) > 500 || isset($seen[$path])) {
                return;
            }
            $seen[$path] = true;
            $pending[] = [$path, $type, $id];
            $report->add('url-aliases.'.$type->value);
        };

        foreach ($this->em->createQuery(
            'SELECT t.slug AS slug, IDENTITY(t.category) AS cid FROM '.CategoryTranslation::class.' t
             JOIN t.category c WHERE c.store = :s AND t.locale = :l AND t.slug <> :e'
        )->setParameter('s', $store)->setParameter('l', $locale)->setParameter('e', '')->getScalarResult() as $r) {
            $push((string) $r['slug'], AliasTarget::Category, (int) $r['cid']);
        }

        foreach ($this->em->createQuery(
            'SELECT t.slug AS slug, IDENTITY(t.page) AS pid FROM '.PageTranslation::class.' t
             JOIN t.page p WHERE p.store = :s AND t.locale = :l AND t.slug <> :e'
        )->setParameter('s', $store)->setParameter('l', $locale)->setParameter('e', '')->getScalarResult() as $r) {
            $push((string) $r['slug'], AliasTarget::Page, (int) $r['pid']);
        }

        foreach ($this->em->createQuery(
            'SELECT t.slug AS slug, v.id AS vid FROM '.VariantTranslation::class.' t
             JOIN t.variant v, '.IdMap::class.' m
             WHERE m.source = :src AND m.entityType = :vt AND m.newId = v.id
               AND t.locale = :l AND t.slug IS NOT NULL AND t.slug <> :e'
        )
            ->setParameter('src', $source->value)
            ->setParameter('vt', 'variant')
            ->setParameter('l', $locale)
            ->setParameter('e', '')
            ->getScalarResult() as $r) {
            $push((string) $r['slug'], AliasTarget::Variant, (int) $r['vid']);
        }

        if ($dryRun) {
            return;
        }

        $store = $this->em->getReference(Store::class, $storeId);
        $i = 0;
        foreach ($pending as [$path, $type, $id]) {
            $this->em->persist(new UrlAlias($store, $locale, $path, $type, $id));
            if (0 === ++$i % self::BATCH) {
                $this->em->flush();
                $this->em->clear();
                $store = $this->em->getReference(Store::class, $storeId);
            }
        }
        $this->em->flush();
        $this->em->clear();
    }
}
