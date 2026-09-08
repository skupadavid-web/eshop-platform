<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Entity\Catalog\Product;
use App\Entity\Catalog\ProductTranslation;
use App\Entity\Catalog\ProductVariant;
use App\Entity\Catalog\VariantMedia;
use App\Entity\Catalog\VariantSize;
use App\Entity\Catalog\VariantTranslation;
use App\Entity\Pricing\Price;
use App\Entity\Shop\Store;
use App\Enum\ContentSource;
use App\Migration\Colors;
use App\Migration\IdMapper;
use App\Migration\LegacyDb;
use App\Migration\MigrationReport;
use App\Migration\Slug;
use App\Migration\Source;
use Doctrine\ORM\EntityManagerInterface;

/** {prefix}shopdata -> Product (+translation, price); {prefix}shopdata_podrobnosti -> variants (+translation, sizes, media). */
final class ProductStep implements MigrationStep
{
    public function __construct(
        private readonly LegacyDb $db,
        private readonly EntityManagerInterface $em,
        private readonly IdMapper $ids,
    ) {
    }

    public function name(): string
    {
        return 'products';
    }

    public function supports(): array
    {
        return [Source::Tsp, Source::Tsl, Source::Cd];
    }

    public function run(Source $source, MigrationReport $report, bool $dryRun): void
    {
        $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $source->storeCode()]);
        if (!$store instanceof Store) {
            $report->warn('store missing — run app:seed');

            return;
        }
        $this->ids->warmup($source, 'product');
        $this->ids->warmup($source, 'variant');
        $locale = $source->locale();

        // keep each flush() well under ~15k pending entities: the commit-order
        // TopologicalSort is the memory ceiling on a big catalogue (tsl ≈ 90k variants / 530k sizes).
        $flushEvery = 100;
        $sinceFlush = 0;

        foreach ($this->db->iterate(sprintf(
            'SELECT id, product, subtitle, description, description_short, keywords, price_vat, nakupni_cena,
                    availabality, velikosti_1, manufacturer, typ_zbozi, technologie, publikace, product_online
             FROM %s WHERE availabality NOT LIKE "-1"',
            $source->t('shopdata'),
        )) as $r) {
            $oldId = (int) $r['id'];
            if (null !== $this->ids->get($source, 'product', $oldId)) {
                $report->add('products.skipped');
                continue;
            }
            if ('' === trim((string) $r['product'])) {
                $report->warn('shopdata #'.$oldId.' has empty name — skipped');
                continue;
            }

            $p = new Product();
            $p->legacyId = $oldId;
            $p->manufacturer = self::clean($r['manufacturer'], 120);
            $p->productType = self::clean($r['typ_zbozi'], 60);
            $p->printTechnology = self::clean($r['technologie'], 60);
            $p->published = '1' === (string) $r['publikace'] || 1 === (int) $r['product_online'];

            $t = new ProductTranslation($p, $locale);
            $t->name = mb_substr(trim((string) $r['product']), 0, 255);
            $t->subtitle = self::clean($r['subtitle'], 255);
            $t->description = self::clean($r['description']);
            $t->descriptionShort = self::clean($r['description_short']);
            $t->metaKeywords = self::clean($r['keywords']);
            $t->contentSource = mb_strlen(strip_tags((string) $r['description'])) < 120
                ? ContentSource::Template : ContentSource::Original;
            $p->translations->add($t);

            $sizes = array_values(array_filter(array_map('trim', explode(',', (string) $r['velikosti_1']))));

            $variantRows = $this->db->all(sprintf(
                'SELECT id, nazev_varianty, img_url, alias_varianta, poradi
                 FROM %s WHERE id_produktu = ? ORDER BY poradi, id',
                $source->t('shopdata_podrobnosti'),
            ), [$oldId]);

            if ([] === $variantRows) {
                // product without variants: synthesise one "default" variant so sizes have a home
                $variantRows = [['id' => -$oldId, 'nazev_varianty' => '', 'img_url' => null, 'alias_varianta' => null, 'poradi' => 0]];
            }

            foreach ($variantRows as $vi => $v) {
                $variant = new ProductVariant($p);
                $variant->legacyId = (int) $v['id'] > 0 ? (int) $v['id'] : null;
                $variant->color = mb_substr(trim((string) $v['nazev_varianty']) ?: 'základní', 0, 60);
                $variant->colorHex = Colors::hex($variant->color);
                $variant->position = (int) $v['poradi'] ?: $vi;

                $vt = new VariantTranslation($variant, $locale);
                $vt->name = $variant->color;
                if (!empty($v['alias_varianta'])) {
                    $vt->slug = Slug::fromLegacyAlias((string) $v['alias_varianta']);
                }
                $variant->translations->add($vt);

                if (!empty($v['img_url'])) {
                    $variant->media->add(new VariantMedia($variant, ltrim((string) $v['img_url'], '/')));
                }
                foreach ($sizes as $si => $sz) {
                    $vs = new VariantSize($variant, mb_substr($sz, 0, 20));
                    $vs->position = $si;
                    $variant->sizes->add($vs);
                }
                $p->variants->add($variant);
                $report->add('variants');
                $report->add('sizes', \count($sizes));
            }

            $price = new Price($store, $p);
            $price->price = (int) round((float) $r['price_vat']);
            $price->purchasePrice = '' === (string) $r['nakupni_cena'] ? null : (int) round((float) $r['nakupni_cena']);
            $price->currency = $store->currency;
            $this->em->persist($price);

            $this->em->persist($p);
            $report->add('products');

            if (!$dryRun && ++$sinceFlush >= $flushEvery) {
                $this->finish($source, $report);
                $sinceFlush = 0;
                $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $source->storeCode()]);
            }
        }

        if (!$dryRun) {
            $this->finish($source, $report);
        }
    }

    private function finish(Source $source, MigrationReport $report): void
    {
        $this->em->flush();
        foreach ($this->em->getUnitOfWork()->getIdentityMap()[Product::class] ?? [] as $p) {
            if (!$p instanceof Product || null === $p->id || null === $p->legacyId) {
                continue;
            }
            $this->ids->set($source, 'product', $p->legacyId, $p->id);
            foreach ($p->variants as $v) {
                if (null !== $v->legacyId && null !== $v->id) {
                    $this->ids->set($source, 'variant', $v->legacyId, $v->id);
                }
            }
        }
        $this->em->flush();
        $this->em->clear();
    }

    private static function clean(mixed $v, int $max = 65535): ?string
    {
        $v = trim((string) $v);

        return '' === $v ? null : mb_substr($v, 0, $max);
    }
}
