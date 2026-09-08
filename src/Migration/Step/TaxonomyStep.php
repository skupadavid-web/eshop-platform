<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Entity\Content\Page;
use App\Entity\Content\PageTranslation;
use App\Entity\Shop\Store;
use App\Entity\Taxonomy\Category;
use App\Entity\Taxonomy\CategoryTranslation;
use App\Enum\PageType;
use App\Migration\IdMapper;
use App\Migration\LegacyDb;
use App\Migration\MigrationReport;
use App\Migration\Source;
use Doctrine\ORM\EntityManagerInterface;

/** Old {prefix}stranky -> Category (listing templates) or Page (content), + parent links + main-menu flag. */
final class TaxonomyStep implements MigrationStep
{
    /** Any old template whose name starts with this renders a product listing = a Category. */
    private const CATEGORY_TEMPLATE_PREFIX = 'vypis_zbozi';

    /** Old templates that are checkout/account plumbing, not real content — skipped entirely. */
    private const SYSTEM_TEMPLATES = [
        'kosik.php', 'detail_zbozi.php', 'detail_zbozi_2.php', 'detail_zbozi_3.php',
        'rekapitulace.php', 'rekapitulace_2.php', 'odeslani_objednavky.php', 'odeslani_objednavky_2.php',
        'doprava_platba.php', 'doprava_platba_2.php', 'vyhledavani.php', 'registrace_uzivatele.php',
        'editace_uzivatele.php', 'osobni_udaje.php', 'test_odeslani.php', 'kontrola_objednavky.php',
    ];

    public function __construct(
        private readonly LegacyDb $db,
        private readonly EntityManagerInterface $em,
        private readonly IdMapper $ids,
    ) {
    }

    public function name(): string
    {
        return 'taxonomy';
    }

    public function supports(): array
    {
        return [Source::Tsp, Source::Tsl, Source::Cd];
    }

    public function run(Source $source, MigrationReport $report, bool $dryRun): void
    {
        $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $source->storeCode()]);
        if (!$store instanceof Store) {
            $report->warn('store '.$source->storeCode().' not found — run seed first');

            return;
        }
        $this->ids->warmup($source, 'category');
        $this->ids->warmup($source, 'page');
        $locale = $source->locale();

        $rows = $this->db->all(sprintf(
            'SELECT s.id, s.title, s.subtitle, s.alias, s.sablona, s.parent, s.poradi, s.publikace, s.menu_stav,
                    o.text_stranky, o.keywords, o.description
             FROM %s s LEFT JOIN %s o ON o.id = s.id
             WHERE s.display_frontend = 1 AND (s.alias IS NOT NULL AND s.alias <> "")',
            $source->t('stranky'),
            $source->t('obsah_stranek'),
        ));

        // pass 1: create nodes
        /** @var array<int,Category|Page> $created */
        $created = [];
        $parents = [];
        foreach ($rows as $r) {
            $oldId = (int) $r['id'];
            $sablona = (string) $r['sablona'];
            $alias = ltrim((string) $r['alias'], '/');
            if (str_contains($alias, '://') || \in_array($sablona, self::SYSTEM_TEMPLATES, true)) {
                $report->add('taxonomy.system_skipped');
                continue;
            }
            $isCategory = str_starts_with($sablona, self::CATEGORY_TEMPLATE_PREFIX);
            $position = self::orderKey((string) $r['poradi']);
            $inMenu = str_contains((string) ($r['menu_stav'] ?? ''), 'class=open');

            if ($isCategory) {
                if (null !== $this->ids->get($source, 'category', $oldId)) {
                    $report->add('taxonomy.category.skipped');
                    continue;
                }
                $cat = new Category($store);
                $cat->legacyId = $oldId;
                $cat->position = $position;
                $cat->published = (bool) $r['publikace'];
                $cat->showInMenu = $inMenu;
                // keep the old listing template as a layout hint for E4 (vypis_zbozi_spec_8 -> "spec_8")
                $layout = trim(str_replace(['vypis_zbozi', '.php'], '', $sablona), '_');
                $cat->listingLayout = mb_substr('' === $layout ? 'grid' : $layout, 0, 40);
                $t = new CategoryTranslation($cat, $locale);
                $t->name = mb_substr((string) $r['title'], 0, 255);
                $t->slug = mb_substr($alias, 0, 255);
                $t->metaDescription = $r['description'] ? mb_substr((string) $r['description'], 0, 255) : null;
                $t->bodyHtml = $r['text_stranky'] ? (string) $r['text_stranky'] : null;
                $cat->translations->add($t);
                $created[$oldId] = $cat;
                $report->add($inMenu ? 'taxonomy.category.menu' : 'taxonomy.category');
            } else {
                if (null !== $this->ids->get($source, 'page', $oldId)) {
                    $report->add('taxonomy.page.skipped');
                    continue;
                }
                $page = new Page($store);
                $page->legacyId = $oldId;
                $page->type = PageType::Content;
                $page->position = $position;
                $page->published = (bool) $r['publikace'];
                $t = new PageTranslation($page, $locale);
                $t->title = mb_substr((string) $r['title'], 0, 255);
                $t->slug = mb_substr($alias, 0, 255);
                $t->metaDescription = $r['description'] ? mb_substr((string) $r['description'], 0, 255) : null;
                $t->bodyHtml = $r['text_stranky'] ? (string) $r['text_stranky'] : null;
                $page->translations->add($t);
                $created[$oldId] = $page;
                $report->add('taxonomy.page');
            }
            $parents[$oldId] = $r['parent'] ? (int) $r['parent'] : null;
        }

        if ($dryRun) {
            return;
        }

        foreach ($created as $node) {
            $this->em->persist($node);
        }
        $this->em->flush();
        foreach ($created as $oldId => $node) {
            $this->ids->set($source, $node instanceof Category ? 'category' : 'page', $oldId, (int) $node->id);
        }

        // pass 2: parent links (only category->category)
        foreach ($created as $oldId => $node) {
            $parentOld = $parents[$oldId] ?? null;
            if (null === $parentOld || !$node instanceof Category) {
                continue;
            }
            $parentNew = $this->ids->get($source, 'category', $parentOld);
            if (null !== $parentNew) {
                $node->parent = $this->em->getReference(Category::class, $parentNew);
                $report->add('taxonomy.parent_link');
            }
        }
        $this->em->flush();
    }

    /** "002.1" / "99.98" / "003" -> a sortable int (x100), empty -> a large number so it sinks. */
    private static function orderKey(string $poradi): int
    {
        $poradi = trim(str_replace(',', '.', $poradi));
        if ('' === $poradi || !is_numeric($poradi)) {
            return 100000;
        }

        return (int) round((float) $poradi * 100);
    }
}
