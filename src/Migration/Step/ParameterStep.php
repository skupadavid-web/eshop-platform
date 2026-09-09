<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Entity\Catalog\ParameterTemplate;
use App\Entity\Catalog\Product;
use App\Entity\Catalog\ProductParameter;
use App\Entity\Shop\Store;
use App\Enum\ContentSource;
use App\Migration\IdMapper;
use App\Migration\LegacyDb;
use App\Migration\MigrationReport;
use App\Migration\Source;
use Doctrine\ORM\EntityManagerInterface;

/**
 * {prefix}shopdata_list (~3.7M rows, one spec per variant) -> parameters.
 * Near-constant names ("Materiál"…) become one ParameterTemplate per store;
 * genuinely per-product names ("Určeno jako") become a ProductParameter,
 * de-duplicated to the most common value per (product, name). "Barva",
 * "Velikosti", "Produkt", "Zařazeno v kategoriích" are dropped (represented
 * structurally elsewhere).
 */
final class ParameterStep implements MigrationStep
{
    /** name => sort position; these are per-product. */
    private const PER_PRODUCT = ['Určeno jako' => 0];

    /** name => sort position; one value store-wide. */
    private const TEMPLATE = [
        'Materiál' => 1,
        'Gramáž látky' => 2,
        'Střih trička' => 3,
        'Délka rukávů' => 4,
        'Metoda potisku' => 5,
    ];

    public function __construct(
        private readonly LegacyDb $db,
        private readonly EntityManagerInterface $em,
        private readonly IdMapper $ids,
    ) {
    }

    public function name(): string
    {
        return 'parameters';
    }

    public function supports(): array
    {
        return [Source::Tsp, Source::Tsl, Source::Cd];
    }

    public function run(Source $source, MigrationReport $report, bool $dryRun): void
    {
        if (!$this->db->tableExists($source->database(), $source->prefix().'shopdata_list')) {
            return;
        }
        $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $source->storeCode()]);
        if (!$store instanceof Store) {
            return;
        }
        $this->ids->warmup($source, 'product');

        // --- store-wide templates: most common value per constant name ---
        foreach (self::TEMPLATE as $tplName => $pos) {
            $row = $this->db->one(
                'SELECT hodnota FROM '.$source->t('shopdata_list').'
                 WHERE jmeno = ? AND hodnota IS NOT NULL AND hodnota <> ?
                 GROUP BY hodnota ORDER BY COUNT(*) DESC LIMIT 1',
                [$tplName, ''],
            );
            if (null === $row) {
                continue;
            }
            if (!$dryRun) {
                $tpl = new ParameterTemplate($store, $tplName, mb_substr(trim((string) $row['hodnota']), 0, 255));
                $tpl->position = $pos;
                $this->em->persist($tpl);
            }
            $report->add('parameters.template');
        }
        if (!$dryRun) {
            $this->em->flush();
        }

        // --- per-product names ---
        $names = "'".implode("','", array_map('addslashes', array_keys(self::PER_PRODUCT)))."'";
        $sql = 'SELECT id_produktu AS pid, jmeno AS name, hodnota AS value, COUNT(*) AS c
                FROM '.$source->t('shopdata_list').'
                WHERE jmeno IN ('.$names.") AND hodnota IS NOT NULL AND hodnota <> ''
                GROUP BY id_produktu, jmeno, hodnota
                ORDER BY id_produktu, jmeno, c DESC, value";

        $currentKey = null;
        $n = 0;
        foreach ($this->db->iterate($sql) as $r) {
            $key = $r['pid'].'|'.$r['name'];
            if ($key === $currentKey) {
                continue;
            }
            $currentKey = $key;

            $newId = $this->ids->get($source, 'product', (int) $r['pid']);
            if (null === $newId) {
                $report->add('parameters.orphan');
                continue;
            }
            $value = mb_substr(trim((string) $r['value']), 0, 255);
            if ('' === $value) {
                continue;
            }

            if (!$dryRun) {
                $param = new ProductParameter($this->em->getReference(Product::class, $newId), (string) $r['name'], $value);
                $param->position = self::PER_PRODUCT[(string) $r['name']] ?? 9;
                $param->contentSource = ContentSource::Template;
                $this->em->persist($param);
            }
            $report->add('parameters');

            if (!$dryRun && 0 === ++$n % 2000) {
                $this->em->flush();
                $this->em->clear();
                $this->ids->warmup($source, 'product');
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }
    }
}
