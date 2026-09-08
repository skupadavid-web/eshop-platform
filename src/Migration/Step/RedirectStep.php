<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Entity\Content\Redirect;
use App\Entity\Shop\Store;
use App\Migration\MigrationReport;
use App\Migration\Source;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Imports the explicit `RewriteRule ^src$ /target [R=301,L]` lines from the old
 * site's .htaccess (kept in migration/redirects/{source}.htaccess) into the
 * redirects table. Non-301 rules, crawler blocks and the alias fallback are ignored.
 */
final class RedirectStep implements MigrationStep
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function name(): string
    {
        return 'redirects';
    }

    public function supports(): array
    {
        return [Source::Tsp, Source::Tsl, Source::Cd];
    }

    public function run(Source $source, MigrationReport $report, bool $dryRun): void
    {
        $file = $this->projectDir.'/migration/redirects/'.$source->value.'.htaccess';
        if (!is_file($file)) {
            $report->warn('no .htaccess for '.$source->value.' at migration/redirects/'.$source->value.'.htaccess');

            return;
        }
        $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $source->storeCode()]);
        if (!$store instanceof Store) {
            return;
        }

        if (!$dryRun) {
            $this->em->createQuery('DELETE FROM '.Redirect::class." r WHERE r.store = :s AND r.origin = 'htaccess'")
                ->setParameter('s', $store)->execute();
        }

        $seen = [];
        foreach (file($file, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ('' === $line || str_starts_with($line, '#')) {
                continue;
            }
            // RewriteRule ^source$ target [R=301,L]
            if (!preg_match('#^RewriteRule\s+\^?(?<src>\S+?)\$?\s+(?<dst>\S+)\s+\[(?<flags>[^\]]*)\]#i', $line, $m)) {
                continue;
            }
            if (!preg_match('/R=30[12]/i', $m['flags'])) {
                continue; // only real 3xx redirects
            }
            $src = '/'.ltrim(html_entity_decode($m['src']), '/');
            $dst = $m['dst'];
            // skip regex/backreference rules and the generic fallbacks
            if (preg_match('/[()\[\]*+?|]|\$\d|%\{/', $src.$dst) || \in_array($src, ['/', ''], true)) {
                $report->add('redirects.skipped_dynamic');
                continue;
            }
            if (!str_starts_with($dst, 'http')) {
                $dst = '/'.ltrim($dst, '/');
            }
            if ($src === $dst || isset($seen[$src])) {
                continue;
            }
            $seen[$src] = true;
            $code = str_contains($m['flags'], 'R=302') ? 302 : 301;

            if (!$dryRun) {
                $r = new Redirect($store, mb_substr($src, 0, 500), mb_substr($dst, 0, 500));
                $r->code = $code;
                $r->origin = 'htaccess';
                $this->em->persist($r);
            }
            $report->add('redirects');
        }

        if (!$dryRun) {
            $this->em->flush();
        }
    }
}
