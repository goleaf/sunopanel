<?php

declare(strict_types=1);

use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->webhookLog = WebhookLog::factory()->create([
        'source' => 'youtube',
        'event_type' => 'video.uploaded',
        'payload' => ['video_id' => 'test123', 'status' => 'uploaded'],
        'headers' => ['Content-Type' => 'application/json'],
        'status' => 'processed',
        'processed_at' => now(),
    ]);
});

describe('WebhookLog Model', function () {
    it('can be created with factory', function () {
        expect($this->webhookLog)->toBeInstanceOf(WebhookLog::class);
        expect($this->webhookLog->source)->toBe('youtube');
        expect($this->webhookLog->event_type)->toBe('video.uploaded');
        expect($this->webhookLog->status)->toBe('processed');
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'source', 'event_type', 'payload', 'headers', 
            'status', 'error_message', 'processed_at'
        ];
        
        expect($this->webhookLog->getFillable())->toBe($fillable);
    });

    it('casts attributes correctly', function () {
        expect($this->webhookLog->getCasts())->toHaveKey('payload');
        expect($this->webhookLog->getCasts())->toHaveKey('headers');
        expect($this->webhookLog->getCasts())->toHaveKey('processed_at');
        expect($this->webhookLog->getCasts()['payload'])->toBe('array');
        expect($this->webhookLog->getCasts()['headers'])->toBe('array');
    });

    it('has pending scope', function () {
        WebhookLog::factory()->create(['status' => 'failed']);
        
        $pendingLogs = WebhookLog::pending()->get();
        
        expect($pendingLogs)->toHaveCount(0); // Our test log is 'processed'
        
        WebhookLog::factory()->create(['status' => 'pending']);
        $pendingLogs = WebhookLog::pending()->get();
        
        expect($pendingLogs)->toHaveCount(1);
    });

    it('has processed scope', function () {
        WebhookLog::factory()->create(['status' => 'pending']);
        
        $processedLogs = WebhookLog::processed()->get();
        
        expect($processedLogs)->toHaveCount(1);
        expect($processedLogs->first()->id)->toBe($this->webhookLog->id);
    });

    it('has failed scope', function () {
        $failedLog = WebhookLog::factory()->create(['status' => 'failed']);
        
        $failedLogs = WebhookLog::failed()->get();
        
        expect($failedLogs)->toHaveCount(1);
        expect($failedLogs->first()->id)->toBe($failedLog->id);
    });

    it('has by source scope', function () {
        WebhookLog::factory()->create(['source' => 'suno']);
        
        $youtubeLogs = WebhookLog::bySource('youtube')->get();
        
        expect($youtubeLogs)->toHaveCount(1);
        expect($youtubeLogs->first()->id)->toBe($this->webhookLog->id);
    });

    it('has by event type scope', function () {
        WebhookLog::factory()->create(['event_type' => 'video.deleted']);
        
        $uploadedLogs = WebhookLog::byEventType('video.uploaded')->get();
        
        expect($uploadedLogs)->toHaveCount(1);
        expect($uploadedLogs->first()->id)->toBe($this->webhookLog->id);
    });

    it('has recent scope', function () {
        // Create old log
        WebhookLog::factory()->create(['created_at' => now()->subDays(2)]);
        
        $recentLogs = WebhookLog::recent()->get();
        
        expect($recentLogs)->toHaveCount(1);
        expect($recentLogs->first()->id)->toBe($this->webhookLog->id);
    });

    it('can check if it is pending', function () {
        expect($this->webhookLog->isPending())->toBeFalse();
        
        $pendingLog = WebhookLog::factory()->create(['status' => 'pending']);
        expect($pendingLog->isPending())->toBeTrue();
    });

    it('can check if it is processed', function () {
        expect($this->webhookLog->isProcessed())->toBeTrue();
        
        $pendingLog = WebhookLog::factory()->create(['status' => 'pending']);
        expect($pendingLog->isProcessed())->toBeFalse();
    });

    it('can check if it is failed', function () {
        expect($this->webhookLog->isFailed())->toBeFalse();
        
        $failedLog = WebhookLog::factory()->create(['status' => 'failed']);
        expect($failedLog->isFailed())->toBeTrue();
    });

    it('can mark as processed', function () {
        $pendingLog = WebhookLog::factory()->create(['status' => 'pending']);
        
        $pendingLog->markAsProcessed();
        
        expect($pendingLog->fresh()->status)->toBe('processed');
        expect($pendingLog->fresh()->processed_at)->not->toBeNull();
    });

    it('can mark as failed', function () {
        $pendingLog = WebhookLog::factory()->create(['status' => 'pending']);
        
        $pendingLog->markAsFailed('Test error message');
        
        expect($pendingLog->fresh()->status)->toBe('failed');
        expect($pendingLog->fresh()->error_message)->toBe('Test error message');
        expect($pendingLog->fresh()->processed_at)->not->toBeNull();
    });

    it('can get payload value', function () {
        $videoId = $this->webhookLog->getPayloadValue('video_id');
        expect($videoId)->toBe('test123');
        
        $nonExistent = $this->webhookLog->getPayloadValue('non_existent', 'default');
        expect($nonExistent)->toBe('default');
    });

    it('can get header value', function () {
        $contentType = $this->webhookLog->getHeaderValue('Content-Type');
        expect($contentType)->toBe('application/json');
        
        $nonExistent = $this->webhookLog->getHeaderValue('Non-Existent', 'default');
        expect($nonExistent)->toBe('default');
    });

    it('can get processing time', function () {
        $pendingLog = WebhookLog::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subMinutes(5)
        ]);
        
        expect($this->webhookLog->getProcessingTime())->toBeGreaterThan(0);
        expect($pendingLog->getProcessingTime())->toBeNull();
    });

    it('can get age in minutes', function () {
        $oldLog = WebhookLog::factory()->create(['created_at' => now()->subMinutes(30)]);
        
        expect($oldLog->getAgeInMinutes())->toBeGreaterThanOrEqual(30);
        expect($this->webhookLog->getAgeInMinutes())->toBeLessThan(1);
    });

    it('can get formatted status', function () {
        expect($this->webhookLog->getFormattedStatus())->toBe('Processed');
        
        $pendingLog = WebhookLog::factory()->create(['status' => 'pending']);
        expect($pendingLog->getFormattedStatus())->toBe('Pending');
        
        $failedLog = WebhookLog::factory()->create(['status' => 'failed']);
        expect($failedLog->getFormattedStatus())->toBe('Failed');
    });

    it('can get summary', function () {
        $summary = $this->webhookLog->getSummary();
        
        expect($summary)->toHaveKey('id');
        expect($summary)->toHaveKey('source');
        expect($summary)->toHaveKey('event_type');
        expect($summary)->toHaveKey('status');
        expect($summary)->toHaveKey('created_at');
        expect($summary)->toHaveKey('processed_at');
        expect($summary['source'])->toBe('youtube');
        expect($summary['event_type'])->toBe('video.uploaded');
    });

    it('can be converted to array', function () {
        $array = $this->webhookLog->toArray();
        
        expect($array)->toHaveKey('id');
        expect($array)->toHaveKey('source');
        expect($array)->toHaveKey('event_type');
        expect($array)->toHaveKey('payload');
        expect($array)->toHaveKey('headers');
        expect($array)->toHaveKey('status');
        expect($array['payload'])->toBeArray();
        expect($array['headers'])->toBeArray();
    });

    it('can get statistics', function () {
        WebhookLog::factory()->create(['status' => 'pending']);
        WebhookLog::factory()->create(['status' => 'failed']);
        
        $stats = WebhookLog::getStatistics();
        
        expect($stats)->toHaveKey('total');
        expect($stats)->toHaveKey('pending');
        expect($stats)->toHaveKey('processed');
        expect($stats)->toHaveKey('failed');
        expect($stats['total'])->toBe(3);
        expect($stats['pending'])->toBe(1);
        expect($stats['processed'])->toBe(1);
        expect($stats['failed'])->toBe(1);
    });

    it('can cleanup old logs', function () {
        // Create old logs
        WebhookLog::factory()->count(5)->create(['created_at' => now()->subDays(35)]);
        
        $deletedCount = WebhookLog::cleanupOldLogs(30);
        
        expect($deletedCount)->toBe(5);
        expect(WebhookLog::count())->toBe(1); // Only our test log remains
    });
}); 