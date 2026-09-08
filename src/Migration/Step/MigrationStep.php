<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Migration\MigrationReport;
use App\Migration\Source;

interface MigrationStep
{
    public function name(): string;

    /** @return list<Source> sources this step handles */
    public function supports(): array;

    public function run(Source $source, MigrationReport $report, bool $dryRun): void;
}
