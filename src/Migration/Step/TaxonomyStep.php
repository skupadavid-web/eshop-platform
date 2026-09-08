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

/** Old {prefix}stranky -> Category (listing templates) or Page (content), + parent links. */
final class TaxonomyStep implements MigrationStep
{
    /** Any old template whose name starts with this renders a product listing = a Category. */
    private const CATEGORY_TEMPLATE_PREFIX = 'vypis_zbozi';

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
            'SELECT s.id, s.title, s.subtitle, s.alias, s.sablona, s.parent, s.poradi, s.publikace,
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
            $isCategory = str_starts_with($sablona, self::CATEGORY_TEMPLATE_PREFIX);
            $alias = ltrim((string) $r['alias'], '/');
            if (str_contains($alias, '://')) {
                continue; // home row stores a full URL
            }

            if ($isCategory) {
                if (null !== $this->ids->get($source, 'category', $oldId)) {
                    $report->add('taxonomy.category.skipped');
                    continue;
                }
                $cat = new Category($store);
                $cat->legacyId = $oldId;
                $cat->position = (int) $r['poradi'];
                $cat->published = (bool) $r['publikace'];
                // keep the old listing template as a layout hint for E4 (vypis_zbozi_spec_8 -> "spec_8")
                $layout = trim(str_replace(['vypis_zbozi', '.php'], '', $sablona), '_');
                $cat->listingLayout = '' === $layout ? 'grid' : $layout;
                $t = new CategoryTranslation($cat, $locale);
                $t->name = (string) $r['title'];
                $t->slug = $alias;
                $t->metaDescription = $r['description'] ? (string) $r['description'] : null;
                $t->bodyHtml = $r['text_stranky'] ? (string) $r['text_stranky'] : null;
                $cat->translations->add($t);
                $created[$oldId] = $cat;
                $report->add('taxonomy.category');
            } else {
                if (null !== $this->ids->get($source, 'page', $oldId)) {
                    $report->add('taxonomy.page.skipped');
                    continue;
                }
                $page = new Page($store);
                $page->legacyId = $oldId;
                $page->type = PageType::Content;
                $page->position = (int) $r['poradi'];
                $page->published = (bool) $r['publikace'];
                $t = new PageTranslation($page, $locale);
                $t->title = (string) $r['title'];
                $t->slug = $alias;
                $t->metaDescription = $r['description'] ? (string) $r['description'] : null;
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

        foreach ($created as $oldId => $node) {
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
}
