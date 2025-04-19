<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class LintPsr12Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lint:psr12 {--fix : Fix PSR-12 issues automatically} {--path= : Specific path to lint}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lint PHP files according to PSR-12 standards using Laravel Pint';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting PSR-12 linting...');

        $fix = $this->option('fix');
        $path = $this->option('path');

        // Use Laravel Pint for linting
        $command = ['./vendor/bin/pint'];

        // Add path if specified, otherwise use predefined safe paths
        if ($path) {
            $command[] = $path;
        } else {
            // Target only PHP code directories to avoid issues
            $command[] = 'app';
            $command[] = 'config';
            $command[] = 'database';
            $command[] = 'routes';
            $command[] = 'tests';
        }

        if (! $fix) {
            $command[] = '--test';
        }

        $this->info($fix ? 'Fixing PSR-12 issues...' : 'Checking for PSR-12 issues...');

        $process = new Process($command, base_path());
        $process->setTimeout(60);

        $process->run(function ($type, $buffer) {
            if ($type === Process::ERR) {
                $this->error($buffer);
            } else {
                $this->line($buffer);
            }
        });

        if ($process->isSuccessful()) {
            $this->info($fix ? 'All files have been fixed according to PSR-12 standards!' : 'All files are compliant with PSR-12 standards!');

            return Command::SUCCESS;
        } else {
            $this->error($fix ? 'Failed to fix some PSR-12 issues.' : 'Some files are not compliant with PSR-12 standards.');

            return Command::FAILURE;
        }
    }
}
