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
        $locale = $source->locale();

        if (!$dryRun) {
            // rebuild: drop this store's aliases first so the step is repeatable
            $this->em->createQuery('DELETE FROM App\Entity\Content\UrlAlias a WHERE a.store = :s')
                ->setParameter('s', $store)->execute();
        }

        /** @var array<string,bool> $seen */
        $seen = [];
        $add = function (string $path, AliasTarget $type, int $id) use ($store, $locale, &$seen, $report, $dryRun): void {
            $path = '/'.ltrim($path, '/');
            if ('' === trim($path, '/') || isset($seen[$path])) {
                return;
            }
            $seen[$path] = true;
            if (!$dryRun) {
                $this->em->persist(new UrlAlias($store, $locale, $path, $type, $id));
            }
            $report->add('url-aliases.'.$type->value);
        };

        foreach ($this->em->createQuery(
            'SELECT t.slug AS slug, IDENTITY(t.category) AS cid FROM '.CategoryTranslation::class.' t JOIN t.category c WHERE c.store = :s AND t.locale = :l'
        )->setParameter('s', $store)->setParameter('l', $locale)->toIterable() as $r) {
            if ('' !== (string) $r['slug']) {
                $add((string) $r['slug'], AliasTarget::Category, (int) $r['cid']);
            }
        }

        foreach ($this->em->createQuery(
            'SELECT t.slug AS slug, IDENTITY(t.page) AS pid FROM '.PageTranslation::class.' t JOIN t.page p WHERE p.store = :s AND t.locale = :l'
        )->setParameter('s', $store)->setParameter('l', $locale)->toIterable() as $r) {
            if ('' !== (string) $r['slug']) {
                $add((string) $r['slug'], AliasTarget::Page, (int) $r['pid']);
            }
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
            ->toIterable() as $r) {
            $add((string) $r['slug'], AliasTarget::Variant, (int) $r['vid']);
        }

        if (!$dryRun) {
            $this->em->flush();
        }
    }
}
