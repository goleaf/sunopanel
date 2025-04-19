<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class TestStyleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:style {--check : Only check style without fixing} {--details : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix coding style in test files using Laravel Pint';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $check = $this->option('check');
        $showDetails = $this->option('details');
        
        $this->info($check ? 'Checking test files style...' : 'Fixing test files style...');
        
        $command = ['./vendor/bin/pint', '--preset', 'laravel', 'tests'];
        
        if ($check) {
            $command[] = '--test';
        }
        
        if ($showDetails) {
            $command[] = '-v';
        }
        
        $process = new Process($command, base_path());
        $process->setTimeout(60);
        
        if ($showDetails) {
            $process->run(function ($type, $buffer) {
                $this->output->write($buffer);
            });
        } else {
            $process->run();
        }
        
        if ($process->isSuccessful()) {
            $this->info($check 
                ? 'All test files comply with coding standards!'
                : 'Test files coding style has been fixed!'
            );
            return Command::SUCCESS;
        } else {
            $this->error($check 
                ? 'Some test files do not comply with coding standards.'
                : 'Failed to fix coding style in test files.'
            );
            
            if (!$showDetails) {
                $this->warn('Use --details to see detailed output');
                $this->line($process->getOutput());
            }
            
            return Command::FAILURE;
        }
    }
} 