<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Entity\Catalog\Product;
use App\Entity\Taxonomy\Category;
use App\Entity\Taxonomy\CategoryProduct;
use App\Migration\IdMapper;
use App\Migration\LegacyDb;
use App\Migration\MigrationReport;
use App\Migration\Source;
use Doctrine\ORM\EntityManagerInterface;

/** {prefix}stranky_zbozi_xy -> CategoryProduct (skips links to products/categories that no longer exist). */
final class CategoryProductStep implements MigrationStep
{
    public function __construct(
        private readonly LegacyDb $db,
        private readonly EntityManagerInterface $em,
        private readonly IdMapper $ids,
    ) {
    }

    public function name(): string
    {
        return 'category-products';
    }

    public function supports(): array
    {
        return [Source::Tsp, Source::Tsl, Source::Cd];
    }

    public function run(Source $source, MigrationReport $report, bool $dryRun): void
    {
        $this->ids->warmup($source, 'category');
        $this->ids->warmup($source, 'product');

        /** @var array<string,true> $seen "catNew:prodNew" — the legacy xy table carries duplicate pairs */
        $seen = [];
        $n = 0;
        foreach ($this->db->iterate(sprintf(
            'SELECT id_zbozi, id_stranky, poradi FROM %s',
            $source->t('stranky_zbozi_xy'),
        )) as $r) {
            $catNew = $this->ids->get($source, 'category', (int) $r['id_stranky']);
            $prodNew = $this->ids->get($source, 'product', (int) $r['id_zbozi']);
            if (null === $catNew || null === $prodNew) {
                $report->add('category-products.orphan');
                continue;
            }
            $pairKey = $catNew.':'.$prodNew;
            if (isset($seen[$pairKey])) {
                $report->add('category-products.duplicate');
                continue;
            }
            $seen[$pairKey] = true;
            $cp = new CategoryProduct(
                $this->em->getReference(Category::class, $catNew),
                $this->em->getReference(Product::class, $prodNew),
            );
            $cp->position = (int) $r['poradi'];
            if (!$dryRun) {
                $this->em->persist($cp);
            }
            $report->add('category-products');
            if (!$dryRun && 0 === ++$n % 500) {
                $this->em->flush();
                $this->em->clear();
                $this->ids->warmup($source, 'category');
                $this->ids->warmup($source, 'product');
            }
        }
        if (!$dryRun) {
            $this->em->flush();
        }
    }
}
