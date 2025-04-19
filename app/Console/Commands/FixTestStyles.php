<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class FixTestStyles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:style-fix {--path= : Specific path to fix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix common style issues in test files and run Pint on them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->option('path') ?? 'tests';
        $fullPath = base_path($path);

        $this->info('Fixing common style issues in test files...');

        // First convert test annotations to attributes if needed
        $this->call('test:convert-attributes');

        // Then run Pint on the tests directory
        $this->info('Running Laravel Pint on test files...');
        
        $command = [
            './vendor/bin/pint',
            $path,
        ];
        
        $process = new Process($command, base_path());
        $process->setTimeout(120);
        
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if ($process->isSuccessful()) {
            $this->info('Tests styling completed successfully!');
            return Command::SUCCESS;
        } else {
            $this->error('There was an error fixing the test styles.');
            return Command::FAILURE;
        }
    }
} 