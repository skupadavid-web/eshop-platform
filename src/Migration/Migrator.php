<?php

declare(strict_types=1);

namespace App\Migration;

use App\Entity\Migration\ImportBatch;
use App\Migration\Step\MigrationStep;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

final class Migrator
{
    /** @var list<MigrationStep> ordered */
    private array $steps;

    /**
     * @param iterable<MigrationStep> $steps
     */
    public function __construct(
        iterable $steps,
        private readonly ManagerRegistry $registry,
        private readonly LoggerInterface $logger,
    ) {
        $this->steps = $this->order(iterator_to_array($steps, false));
    }

    private function em(): EntityManagerInterface
    {
        $em = $this->registry->getManager();
        \assert($em instanceof EntityManagerInterface);

        return $em;
    }

    /** @return list<string> */
    public function stepNames(): array
    {
        return array_map(static fn (MigrationStep $s) => $s->name(), $this->steps);
    }

    /**
     * @param list<string> $only
     *
     * @return array<string,MigrationReport>
     */
    public function run(Source $source, array $only, bool $dryRun, ?callable $onStep = null): array
    {
        $reports = [];
        foreach ($this->steps as $step) {
            if (!\in_array($source, $step->supports(), true)) {
                continue;
            }
            if ([] !== $only && !\in_array($step->name(), $only, true)) {
                continue;
            }
            $onStep && $onStep($step->name(), 'start');
            $report = new MigrationReport();
            $batch = new ImportBatch($source->value, $step->name());
            try {
                $step->run($source, $report, $dryRun);
            } catch (\Throwable $e) {
                $report->warn('FATAL: '.$e->getMessage());
                $this->logger->error('migration step failed', ['step' => $step->name(), 'exception' => $e]);
                // a failed flush closes the EM — reopen it so the remaining steps can still run
                if (!$this->em()->isOpen()) {
                    $this->registry->resetManager();
                }
            }
            $batch->finishedAt = new \DateTimeImmutable();
            $batch->processed = array_sum($report->counts);
            $batch->report = $report->toArray();
            if (!$dryRun) {
                try {
                    $em = $this->em();
                    $em->persist($batch);
                    $em->flush();
                } catch (\Throwable $e) {
                    $this->logger->error('could not persist ImportBatch', ['step' => $step->name(), 'exception' => $e]);
                }
            }
            $reports[$step->name()] = $report;
            $onStep && $onStep($step->name(), 'done');
        }

        return $reports;
    }

    /**
     * @param list<MigrationStep> $steps
     *
     * @return list<MigrationStep>
     */
    private function order(array $steps): array
    {
        $rank = ['taxonomy' => 10, 'products' => 20, 'category-products' => 30, 'url-aliases' => 40, 'redirects' => 45, 'customers' => 50, 'orders' => 60];
        usort($steps, static fn (MigrationStep $a, MigrationStep $b) => ($rank[$a->name()] ?? 99) <=> ($rank[$b->name()] ?? 99));

        return $steps;
    }
}
