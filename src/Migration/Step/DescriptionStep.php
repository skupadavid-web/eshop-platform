<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Entity\Catalog\ProductTranslation;
use App\Entity\Catalog\VariantTranslation;
use App\Enum\ContentSource;
use App\Migration\IdMapper;
use App\Migration\LegacyDb;
use App\Migration\MigrationReport;
use App\Migration\Source;
use Doctrine\ORM\EntityManagerInterface;

/**
 * {prefix}shopdata_podrobnosti_rozsirene -> per-variant descriptions.
 *   description        -> VariantTranslation.descriptionExtended  (colour-specific body)
 *   description_short  -> VariantTranslation.metaDescription      (feeds <meta description>)
 * Phase 2 copies the default variant's copy up to ProductTranslation. Rows past
 * the truncated export keep the generic text ProductStep imported from
 * {prefix}shopdata.
 */
final class DescriptionStep implements MigrationStep
{
    public function __construct(
        private readonly LegacyDb $db,
        private readonly EntityManagerInterface $em,
        private readonly IdMapper $ids,
    ) {
    }

    public function name(): string
    {
        return 'descriptions';
    }

    public function supports(): array
    {
        return [Source::Tsp, Source::Tsl, Source::Cd];
    }

    public function run(Source $source, MigrationReport $report, bool $dryRun): void
    {
        if (!$this->db->tableExists($source->database(), $source->prefix().'shopdata_podrobnosti_rozsirene')) {
            return;
        }
        $this->ids->warmup($source, 'variant');
        $locale = $source->locale();

        // --- phase 1: per-variant text ---
        $vtRepo = $this->em->getRepository(VariantTranslation::class);
        $n = 0;
        foreach ($this->db->iterate(
            'SELECT id_podrobnosti_puvodni AS vlid, description_short AS ds, description AS d
             FROM '.$source->t('shopdata_podrobnosti_rozsirene').'
             ORDER BY id_podrobnosti_puvodni'
        ) as $r) {
            $variantNew = $this->ids->get($source, 'variant', (int) $r['vlid']);
            if (null === $variantNew) {
                $report->add('descriptions.orphan');
                continue;
            }
            $body = self::clean($r['d']);
            $short = self::clean($r['ds']);
            if (null === $body && null === $short) {
                continue;
            }

            if (!$dryRun) {
                $vt = $vtRepo->findOneBy(['variant' => $variantNew, 'locale' => $locale]);
                if ($vt instanceof VariantTranslation) {
                    if (null !== $body) {
                        $vt->descriptionExtended = $body;
                        if (mb_strlen(strip_tags($body)) > 200) {
                            $vt->contentSource = ContentSource::Original;
                        }
                    }
                    if (null !== $short) {
                        $vt->metaDescription = mb_substr($short, 0, 255);
                    }
                }
            }
            $report->add('descriptions');

            if (!$dryRun && 0 === ++$n % 1500) {
                $this->em->flush();
                $this->em->clear();
                $this->ids->warmup($source, 'variant');
                $vtRepo = $this->em->getRepository(VariantTranslation::class);
            }
        }
        if (!$dryRun) {
            $this->em->flush();
            $this->em->clear();
        }

        if ($dryRun) {
            return;
        }

        // --- phase 2: lift the default variant's copy up to the product ---
        $ptRepo = $this->em->getRepository(ProductTranslation::class);
        $m = 0;
        /** @var list<array{pid:int,body:?string,short:?string}> $defaults */
        $defaults = $this->em->createQuery(
            'SELECT IDENTITY(v.product) AS pid, vt.descriptionExtended AS body, vt.metaDescription AS short
             FROM '.VariantTranslation::class.' vt JOIN vt.variant v
             WHERE v.isDefault = true AND vt.locale = :l
               AND (vt.descriptionExtended IS NOT NULL OR vt.metaDescription IS NOT NULL)'
        )->setParameter('l', $locale)->getArrayResult();

        foreach ($defaults as $r) {
            $pt = $ptRepo->findOneBy(['product' => (int) $r['pid'], 'locale' => $locale]);
            if (!$pt instanceof ProductTranslation) {
                continue;
            }
            if (null !== $r['body']) {
                $pt->description = (string) $r['body'];
                if (mb_strlen(strip_tags((string) $r['body'])) > 200) {
                    $pt->contentSource = ContentSource::Original;
                }
            }
            if (null !== $r['short']) {
                $pt->descriptionShort = (string) $r['short'];
            }
            $report->add('descriptions.product');

            if (0 === ++$m % 2000) {
                $this->em->flush();
                $this->em->clear();
                $ptRepo = $this->em->getRepository(ProductTranslation::class);
            }
        }
        $this->em->flush();
    }

    private static function clean(mixed $v): ?string
    {
        $v = trim((string) $v);

        return '' === $v ? null : $v;
    }
}
