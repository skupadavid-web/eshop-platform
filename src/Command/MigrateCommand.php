<?php

declare(strict_types=1);

namespace App\Command;

use App\Migration\Migrator;
use App\Migration\Source;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:migrate', description: 'Migrate a legacy e-shop into the new schema (idempotent).')]
final class MigrateCommand extends Command
{
    public function __construct(private readonly Migrator $migrator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source', 's', InputOption::VALUE_REQUIRED, 'tsp | tsl | cd')
            ->addOption('only', 'o', InputOption::VALUE_REQUIRED, 'comma-separated step names ('.implode(', ', $this->migrator->stepNames()).')')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'read only, no writes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $src = Source::tryFrom((string) $input->getOption('source'));
        if (null === $src || Source::Central === $src) {
            $io->error('Use --source with tsp | tsl | cd');

            return Command::INVALID;
        }
        $only = array_filter(array_map('trim', explode(',', (string) $input->getOption('only'))));
        $dry = (bool) $input->getOption('dry-run');

        // Dev-mode profilers keep every SQL statement in memory; a full catalogue import
        // fires hundreds of thousands of queries and will exhaust RAM. Refuse to run with debug on.
        if (filter_var($_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG'), \FILTER_VALIDATE_BOOL)) {
            $io->error('Spusť s --no-debug (nebo APP_ENV=prod) — v debug režimu Symfony sbírá všechny SQL dotazy a dojde paměť.');

            return Command::INVALID;
        }

        $io->title(sprintf('Migrace %s%s', $src->value, $dry ? ' (dry-run)' : ''));
        $reports = $this->migrator->run($src, $only, $dry, static function (string $name, string $phase) use ($io) {
            if ('start' === $phase) {
                $io->section($name);
            }
        });

        $warnings = 0;
        foreach ($reports as $step => $report) {
            $rows = [];
            foreach ($report->counts as $k => $v) {
                $rows[] = [$k, number_format($v, 0, ',', ' ')];
            }
            if ($rows) {
                $io->table(['počítadlo', 'n'], $rows);
            }
            foreach (\array_slice($report->warnings, 0, 15) as $w) {
                $io->warning($w);
                ++$warnings;
            }
            if (\count($report->warnings) > 15) {
                $io->comment('… a dalších '.(\count($report->warnings) - 15).' varování');
            }
        }

        $io->success(sprintf('Hotovo. Varování: %d.', $warnings));

        return Command::SUCCESS;
    }
}
