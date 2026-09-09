<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Entity\Catalog\Product;
use App\Entity\Content\Faq;
use App\Entity\Shop\Store;
use App\Enum\ContentSource;
use App\Migration\IdMapper;
use App\Migration\LegacyDb;
use App\Migration\MigrationReport;
use App\Migration\Source;
use Doctrine\ORM\EntityManagerInterface;

/**
 * {prefix}shopdata_faq -> Faq. The old table is ~1.5M rows but only a handful of
 * distinct questions: three are the same for every product (-> one store-wide Faq
 * each) and one ("Pro koho je tričko určené?") is per-product.
 */
final class FaqStep implements MigrationStep
{
    /** Questions whose answer is the same everywhere -> a single store-wide row. */
    private const GLOBAL_QUESTIONS = [
        'Jaká je rychlost doručení?',
        'Na kolik stupňů se tričko pere?',
        'Jak vybrat správnou velikost?',
    ];

    public function __construct(
        private readonly LegacyDb $db,
        private readonly EntityManagerInterface $em,
        private readonly IdMapper $ids,
    ) {
    }

    public function name(): string
    {
        return 'faq';
    }

    public function supports(): array
    {
        return [Source::Tsp, Source::Tsl, Source::Cd];
    }

    public function run(Source $source, MigrationReport $report, bool $dryRun): void
    {
        if (!$this->db->tableExists($source->database(), $source->prefix().'shopdata_faq')) {
            return;
        }
        $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $source->storeCode()]);
        if (!$store instanceof Store) {
            return;
        }
        $this->ids->warmup($source, 'product');

        if (!$dryRun) {
            $this->em->createQuery('DELETE FROM '.Faq::class.' f WHERE f.store = :s')
                ->setParameter('s', $store)->execute();
        }

        // store-wide questions: pick the most common answer
        $pos = 10;
        foreach (self::GLOBAL_QUESTIONS as $q) {
            $row = $this->db->one(
                'SELECT odpoved FROM '.$source->t('shopdata_faq').'
                 WHERE otazka = ? AND odpoved IS NOT NULL AND odpoved <> ?
                 GROUP BY odpoved ORDER BY COUNT(*) DESC LIMIT 1',
                [$q, ''],
            );
            if (null === $row) {
                continue;
            }
            if (!$dryRun) {
                $faq = new Faq($store, $q, mb_substr(trim((string) $row['odpoved']), 0, 5000));
                $faq->position = $pos;
                $faq->contentSource = ContentSource::Template;
                $this->em->persist($faq);
            }
            $report->add('faq.global');
            ++$pos;
        }

        // per-product questions (everything not global), most common answer per product
        $sql = 'SELECT id_produktu AS pid, otazka AS q, odpoved AS a, COUNT(*) AS c
                FROM '.$source->t('shopdata_faq')."
                WHERE otazka NOT IN ('".implode("','", array_map('addslashes', self::GLOBAL_QUESTIONS))."')
                  AND odpoved IS NOT NULL AND odpoved <> ''
                GROUP BY id_produktu, otazka, odpoved
                ORDER BY id_produktu, otazka, c DESC, a";

        $currentKey = null;
        $n = 0;
        foreach ($this->db->iterate($sql) as $r) {
            $key = $r['pid'].'|'.$r['q'];
            if ($key === $currentKey) {
                continue;
            }
            $currentKey = $key;

            $newId = $this->ids->get($source, 'product', (int) $r['pid']);
            if (null === $newId) {
                $report->add('faq.orphan');
                continue;
            }
            if (!$dryRun) {
                $faq = new Faq($store, mb_substr(trim((string) $r['q']), 0, 255), mb_substr(trim((string) $r['a']), 0, 5000));
                $faq->product = $this->em->getReference(Product::class, $newId);
                $faq->position = 0;
                $faq->contentSource = ContentSource::Template;
                $this->em->persist($faq);
            }
            $report->add('faq');

            if (!$dryRun && 0 === ++$n % 2000) {
                $this->em->flush();
                $this->em->clear();
                $this->ids->warmup($source, 'product');
                $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $source->storeCode()]);
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }
    }
}
