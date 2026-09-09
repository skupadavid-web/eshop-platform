<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Entity\Catalog\ProductVariant;
use App\Entity\Taxonomy\Category;
use App\Entity\Taxonomy\CategoryProduct;
use App\Migration\IdMapper;
use App\Migration\LegacyDb;
use App\Migration\MigrationReport;
use App\Migration\Source;
use Doctrine\ORM\EntityManagerInterface;

/**
 * {prefix}shopdata_orig_popisky -> CategoryProduct.defaultVariant.
 * The old shop picks the first-shown colour of a product per category:
 *   phase 2 (this table, sparse) wins; phase 1 (ProductVariant.isDefault from
 *   shopdata_podrobnosti.zarazeni, done in ProductStep) is the fallback.
 */
final class PrimaryVariantStep implements MigrationStep
{
    public function __construct(
        private readonly LegacyDb $db,
        private readonly EntityManagerInterface $em,
        private readonly IdMapper $ids,
    ) {
    }

    public function name(): string
    {
        return 'primary-variants';
    }

    public function supports(): array
    {
        return [Source::Tsp, Source::Tsl, Source::Cd];
    }

    public function run(Source $source, MigrationReport $report, bool $dryRun): void
    {
        if (!$this->db->tableExists($source->database(), $source->prefix().'shopdata_orig_popisky')) {
            return;
        }
        $this->ids->warmup($source, 'product');
        $this->ids->warmup($source, 'category');

        $cpRepo = $this->em->getRepository(CategoryProduct::class);
        $n = 0;

        foreach ($this->db->iterate(
            'SELECT id_zbozi, id_stranky, barva FROM '.$source->t('shopdata_orig_popisky').
            " WHERE barva IS NOT NULL AND barva <> ''"
        ) as $r) {
            $prodNew = $this->ids->get($source, 'product', (int) $r['id_zbozi']);
            $catNew = $this->ids->get($source, 'category', (int) $r['id_stranky']);
            $color = trim((string) $r['barva']);
            if (null === $prodNew || null === $catNew) {
                $report->add('primary-variants.orphan');
                continue;
            }

            $cp = $cpRepo->findOneBy([
                'category' => $this->em->getReference(Category::class, $catNew),
                'product' => $this->em->getReference(\App\Entity\Catalog\Product::class, $prodNew),
            ]);
            if (!$cp instanceof CategoryProduct) {
                $report->add('primary-variants.no_link');
                continue;
            }

            $variant = $this->em->createQuery(
                'SELECT v FROM '.ProductVariant::class.' v
                 WHERE IDENTITY(v.product) = :p AND v.color = :c ORDER BY v.position, v.id'
            )->setParameter('p', $prodNew)->setParameter('c', $color)->setMaxResults(1)->getOneOrNullResult();
            if (!$variant instanceof ProductVariant) {
                $report->add('primary-variants.no_variant');
                continue;
            }

            $cp->defaultVariant = $variant;
            $report->add('primary-variants');

            if (!$dryRun && 0 === ++$n % 500) {
                $this->em->flush();
                $this->em->clear();
                $this->ids->warmup($source, 'product');
                $this->ids->warmup($source, 'category');
                $cpRepo = $this->em->getRepository(CategoryProduct::class);
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }
    }
}
