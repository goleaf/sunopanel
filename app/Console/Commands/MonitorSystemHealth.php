<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LoggingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class MonitorSystemHealth extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'monitor:health 
                            {--continuous : Run continuously with intervals}
                            {--interval=60 : Interval in seconds for continuous monitoring}
                            {--alert-threshold=critical : Alert threshold (warning|critical)}
                            {--email= : Email address to send alerts to}
                            {--slack= : Slack webhook URL for alerts}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor system health and send alerts when issues are detected';

    public function __construct(
        private readonly LoggingService $loggingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Starting System Health Monitor...');
        
        if ($this->option('continuous')) {
            return $this->runContinuousMonitoring();
        }
        
        return $this->runSingleCheck();
    }

    /**
     * Run continuous monitoring with intervals.
     */
    private function runContinuousMonitoring(): int
    {
        $interval = (int) $this->option('interval');
        $this->info("🔄 Running continuous monitoring with {$interval}s intervals");
        $this->info('Press Ctrl+C to stop monitoring');
        
        while (true) {
            $this->performHealthCheck();
            
            $this->line('');
            $this->info("⏱️  Waiting {$interval} seconds until next check...");
            sleep($interval);
        }
        
        return Command::SUCCESS;
    }

    /**
     * Run a single health check.
     */
    private function runSingleCheck(): int
    {
        $this->performHealthCheck();
        return Command::SUCCESS;
    }

    /**
     * Perform comprehensive health check.
     */
    private function performHealthCheck(): void
    {
        $this->info('🏥 Performing System Health Check...');
        $this->newLine();
        
        $healthData = $this->loggingService->monitorSystemHealth();
        $health = $healthData['health'];
        $alerts = $healthData['alerts'];
        
        // Display system health status
        $this->displayHealthStatus($health);
        
        // Display alerts if any
        if (!empty($alerts)) {
            $this->displayAlerts($alerts);
            $this->sendAlerts($alerts);
        } else {
            $this->info('✅ No alerts detected - System is healthy');
        }
        
        // Log the health check
        $this->loggingService->logEvent('health_check_completed', [
            'status' => $healthData['status'],
            'alerts_count' => count($alerts),
            'timestamp' => now()->toISOString(),
        ]);
        
        $this->newLine();
        $this->info('📊 Health check completed at ' . now()->format('Y-m-d H:i:s'));
    }

    /**
     * Display system health status.
     */
    private function displayHealthStatus(array $health): void
    {
        $this->info('🔧 System Components Status:');
        $this->newLine();
        
        // Database Health
        $dbStatus = $health['database']['connected'] ? '✅' : '❌';
        $dbInfo = $health['database']['connected'] 
            ? "({$health['database']['response_time_ms']}ms)" 
            : "({$health['database']['error']})";
        $this->line("  Database: {$dbStatus} {$dbInfo}");
        
        // Redis Health
        $redisStatus = $health['redis']['connected'] ? '✅' : '❌';
        $redisInfo = $health['redis']['connected'] 
            ? "({$health['redis']['response_time_ms']}ms)" 
            : "({$health['redis']['error']})";
        $this->line("  Redis Cache: {$redisStatus} {$redisInfo}");
        
        // Queue Health
        $queueStatus = $health['queue']['failed_jobs'] < 50 ? '✅' : '⚠️';
        $queueInfo = "({$health['queue']['pending_jobs']} pending, {$health['queue']['failed_jobs']} failed)";
        $this->line("  Queue System: {$queueStatus} {$queueInfo}");
        
        // Storage Health
        $diskUsage = $health['storage']['disk_usage_percent'];
        $storageStatus = $diskUsage < 80 ? '✅' : ($diskUsage < 90 ? '⚠️' : '❌');
        $storageInfo = "({$diskUsage}% used, " . 
                      number_format($health['storage']['disk_free_space'] / 1024 / 1024 / 1024, 1) . 
                      "GB free)";
        $this->line("  Storage: {$storageStatus} {$storageInfo}");
        
        // Performance Metrics
        $this->newLine();
        $this->info('📈 Performance Metrics:');
        $memoryUsage = $health['performance']['memory_usage'];
        $this->line("  Memory: " . number_format($memoryUsage['current'] / 1024 / 1024, 1) . 
                   "MB current, " . number_format($memoryUsage['peak'] / 1024 / 1024, 1) . "MB peak");
        
        // Error Metrics
        $errorRate = $health['errors']['error_rate'] * 100;
        $errorStatus = $errorRate < 1 ? '✅' : ($errorRate < 5 ? '⚠️' : '❌');
        $this->line("  Error Rate: {$errorStatus} {$errorRate}%");
        
        $this->newLine();
    }

    /**
     * Display alerts.
     */
    private function displayAlerts(array $alerts): void
    {
        $this->error('🚨 System Alerts Detected:');
        $this->newLine();
        
        foreach ($alerts as $alert) {
            $icon = $alert['type'] === 'critical' ? '🔴' : '🟡';
            $this->line("  {$icon} [{$alert['service']}] {$alert['message']}");
        }
        
        $this->newLine();
    }

    /**
     * Send alerts via configured channels.
     */
    private function sendAlerts(array $alerts): void
    {
        $threshold = $this->option('alert-threshold');
        $criticalAlerts = array_filter($alerts, fn($alert) => $alert['type'] === 'critical');
        $warningAlerts = array_filter($alerts, fn($alert) => $alert['type'] === 'warning');
        
        $alertsToSend = [];
        
        if ($threshold === 'warning') {
            $alertsToSend = $alerts;
        } elseif ($threshold === 'critical') {
            $alertsToSend = $criticalAlerts;
        }
        
        if (empty($alertsToSend)) {
            return;
        }
        
        $this->info('📧 Sending alerts...');
        
        // Email alerts
        if ($email = $this->option('email')) {
            $this->sendEmailAlert($email, $alertsToSend);
        }
        
        // Slack alerts
        if ($slackWebhook = $this->option('slack')) {
            $this->sendSlackAlert($slackWebhook, $alertsToSend);
        }
        
        // Log alerts
        foreach ($alertsToSend as $alert) {
            Log::channel('single')->log($alert['type'], 'System Alert: ' . $alert['message'], $alert);
        }
    }

    /**
     * Send email alert.
     */
    private function sendEmailAlert(string $email, array $alerts): void
    {
        try {
            // This would integrate with Laravel's mail system
            $this->info("📧 Email alert would be sent to: {$email}");
            $this->line("   Alerts: " . count($alerts));
            
            // Log the email attempt
            $this->loggingService->logEvent('email_alert_sent', [
                'recipient' => $email,
                'alerts_count' => count($alerts),
            ]);
        } catch (\Exception $e) {
            $this->error("Failed to send email alert: {$e->getMessage()}");
            $this->loggingService->logError($e, ['action' => 'send_email_alert']);
        }
    }

    /**
     * Send Slack alert.
     */
    private function sendSlackAlert(string $webhook, array $alerts): void
    {
        try {
            $message = "🚨 SunoPanel System Alert\n\n";
            $message .= "Detected " . count($alerts) . " system issues:\n\n";
            
            foreach ($alerts as $alert) {
                $icon = $alert['type'] === 'critical' ? '🔴' : '🟡';
                $message .= "{$icon} [{$alert['service']}] {$alert['message']}\n";
            }
            
            $message .= "\nTimestamp: " . now()->format('Y-m-d H:i:s T');
            
            $payload = [
                'text' => $message,
                'username' => 'SunoPanel Monitor',
                'icon_emoji' => ':warning:',
            ];
            
            // This would send to Slack webhook
            $this->info("💬 Slack alert would be sent to webhook");
            $this->line("   Message length: " . strlen($message) . " characters");
            
            // Log the Slack attempt
            $this->loggingService->logEvent('slack_alert_sent', [
                'webhook' => substr($webhook, 0, 50) . '...',
                'alerts_count' => count($alerts),
            ]);
        } catch (\Exception $e) {
            $this->error("Failed to send Slack alert: {$e->getMessage()}");
            $this->loggingService->logError($e, ['action' => 'send_slack_alert']);
        }
    }

    /**
     * Get system statistics for display.
     */
    private function getSystemStats(): array
    {
        return [
            'tracks_total' => Cache::remember('stats:tracks:total', 300, fn() => DB::table('tracks')->count()),
            'tracks_processing' => Cache::remember('stats:tracks:processing', 60, fn() => DB::table('tracks')->where('status', 'processing')->count()),
            'tracks_failed' => Cache::remember('stats:tracks:failed', 60, fn() => DB::table('tracks')->where('status', 'failed')->count()),
            'jobs_pending' => Cache::remember('stats:jobs:pending', 60, fn() => DB::table('jobs')->count()),
            'jobs_failed' => Cache::remember('stats:jobs:failed', 60, fn() => DB::table('failed_jobs')->count()),
        ];
    }
}
