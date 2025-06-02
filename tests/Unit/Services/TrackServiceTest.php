<?php

declare(strict_types=1);

use App\Services\TrackService;
use App\Models\Track;
use App\Jobs\ProcessTrack;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->trackService = new TrackService();
    $this->track = Track::factory()->create([
        'status' => 'pending',
        'title' => 'Test Track',
        'mp3_url' => 'https://cdn1.suno.ai/test.mp3',
        'image_url' => 'https://cdn2.suno.ai/test.jpg',
    ]);
    
    Queue::fake();
    Storage::fake('public');
});

describe('TrackService', function () {
    it('can start processing a track', function () {
        $trackId = $this->track->id;
        $result = $this->trackService->startProcessing($this->track);
        
        expect($result)->toBeTrue();
        
        $updatedTrack = Track::where('id', $trackId)->first();
        expect($updatedTrack)->not->toBeNull();
        expect($updatedTrack->status)->toBe('pending');
        expect($updatedTrack->progress)->toBe(0);
        expect($updatedTrack->error_message)->toBeNull();
        
        Queue::assertPushed(ProcessTrack::class, function ($job) use ($trackId) {
            return $job->track->id === $trackId;
        });
    });

    it('cannot start processing already processing track', function () {
        $this->track->update(['status' => 'processing']);
        
        $result = $this->trackService->startProcessing($this->track);
        
        expect($result)->toBeFalse();
        Queue::assertNotPushed(ProcessTrack::class);
    });

    it('can start processing with force redownload', function () {
        $this->track->update(['status' => 'completed']);
        
        // Create fake files
        Storage::disk('public')->put('tracks/mp3/' . $this->track->id . '.mp3', 'fake mp3 content');
        Storage::disk('public')->put('tracks/images/' . $this->track->id . '.jpg', 'fake image content');
        
        $trackId = $this->track->id;
        $result = $this->trackService->startProcessing($this->track, true);
        
        expect($result)->toBeTrue();
        
        $updatedTrack = Track::where('id', $trackId)->first();
        expect($updatedTrack)->not->toBeNull();
        expect($updatedTrack->status)->toBe('pending');
        
        // Check files were deleted
        expect(Storage::disk('public')->exists('tracks/mp3/' . $this->track->id . '.mp3'))->toBeFalse();
        expect(Storage::disk('public')->exists('tracks/images/' . $this->track->id . '.jpg'))->toBeFalse();
        
        Queue::assertPushed(ProcessTrack::class);
    });

    it('can stop processing a track', function () {
        $this->track->update(['status' => 'processing']);
        
        $result = $this->trackService->stopProcessing($this->track);
        
        expect($result)->toBeTrue();
        
        $updatedTrack = Track::where('id', $this->track->id)->first();
        expect($updatedTrack)->not->toBeNull();
        expect($updatedTrack->status)->toBe('stopped');
        expect($updatedTrack->error_message)->toBe('Processing was manually stopped');
    });

    it('cannot stop non-processing track', function () {
        $this->track->update(['status' => 'completed']);
        
        $result = $this->trackService->stopProcessing($this->track);
        
        expect($result)->toBeFalse();
    });

    it('can retry processing a track', function () {
        $this->track->update(['status' => 'failed']);
        
        $result = $this->trackService->retryProcessing($this->track);
        
        expect($result)->toBeTrue();
        
        $updatedTrack = Track::where('id', $this->track->id)->first();
        expect($updatedTrack)->not->toBeNull();
        expect($updatedTrack->status)->toBe('pending');
        expect($updatedTrack->progress)->toBe(0);
        expect($updatedTrack->error_message)->toBeNull();
        
        Queue::assertPushed(ProcessTrack::class);
    });

    it('cannot retry processing track that is not failed or stopped', function () {
        $this->track->update(['status' => 'completed']);
        
        $result = $this->trackService->retryProcessing($this->track);
        
        expect($result)->toBeFalse();
        Queue::assertNotPushed(ProcessTrack::class);
    });

    it('can check if track can be started', function () {
        // Pending track can be started
        $this->track->update(['status' => 'pending']);
        expect($this->trackService->canStart($this->track))->toBeTrue();
        
        // Failed track can be started
        $this->track->update(['status' => 'failed']);
        expect($this->trackService->canStart($this->track))->toBeTrue();
        
        // Stopped track can be started
        $this->track->update(['status' => 'stopped']);
        expect($this->trackService->canStart($this->track))->toBeTrue();
        
        // Processing track cannot be started
        $this->track->update(['status' => 'processing']);
        expect($this->trackService->canStart($this->track))->toBeFalse();
        
        // Completed track cannot be started
        $this->track->update(['status' => 'completed']);
        expect($this->trackService->canStart($this->track))->toBeFalse();
    });

    it('can check if track can be stopped', function () {
        // Processing track can be stopped
        $this->track->update(['status' => 'processing']);
        expect($this->trackService->canStop($this->track))->toBeTrue();
        
        // Pending track can be stopped
        $this->track->update(['status' => 'pending']);
        expect($this->trackService->canStop($this->track))->toBeTrue();
        
        // Completed track cannot be stopped
        $this->track->update(['status' => 'completed']);
        expect($this->trackService->canStop($this->track))->toBeFalse();
        
        // Failed track cannot be stopped
        $this->track->update(['status' => 'failed']);
        expect($this->trackService->canStop($this->track))->toBeFalse();
    });

    it('can check if track can be retried', function () {
        // Failed track can be retried
        $this->track->update(['status' => 'failed']);
        expect($this->trackService->canRetry($this->track))->toBeTrue();
        
        // Stopped track can be retried
        $this->track->update(['status' => 'stopped']);
        expect($this->trackService->canRetry($this->track))->toBeTrue();
        
        // Processing track cannot be retried
        $this->track->update(['status' => 'processing']);
        expect($this->trackService->canRetry($this->track))->toBeFalse();
        
        // Completed track cannot be retried
        $this->track->update(['status' => 'completed']);
        expect($this->trackService->canRetry($this->track))->toBeFalse();
    });

    it('can get processing statistics', function () {
        // Create tracks with different statuses
        Track::factory()->count(3)->create(['status' => 'pending']);
        Track::factory()->count(2)->create(['status' => 'processing']);
        Track::factory()->count(5)->create(['status' => 'completed']);
        Track::factory()->count(1)->create(['status' => 'failed']);
        Track::factory()->count(1)->create(['status' => 'stopped']);
        
        $stats = $this->trackService->getProcessingStats();
        
        expect($stats)->toHaveKey('total');
        expect($stats)->toHaveKey('pending');
        expect($stats)->toHaveKey('processing');
        expect($stats)->toHaveKey('completed');
        expect($stats)->toHaveKey('failed');
        expect($stats)->toHaveKey('stopped');
        
        expect($stats['total'])->toBe(13); // 12 created + 1 from beforeEach
        expect($stats['pending'])->toBe(4); // 3 created + 1 from beforeEach
        expect($stats['processing'])->toBe(2);
        expect($stats['completed'])->toBe(5);
        expect($stats['failed'])->toBe(1);
        expect($stats['stopped'])->toBe(1);
    });

    it('can delete track files during force redownload', function () {
        // Set file paths on the track
        $this->track->update([
            'mp3_path' => 'tracks/mp3/' . $this->track->id . '.mp3',
            'image_path' => 'tracks/images/' . $this->track->id . '.jpg',
            'mp4_path' => 'tracks/mp4/' . $this->track->id . '.mp4',
        ]);
        
        // Create fake files using the track's file paths
        Storage::disk('public')->put($this->track->mp3_path, 'fake mp3 content');
        Storage::disk('public')->put($this->track->image_path, 'fake image content');
        Storage::disk('public')->put($this->track->mp4_path, 'fake mp4 content');
        
        // Verify files exist
        expect(Storage::disk('public')->exists($this->track->mp3_path))->toBeTrue();
        expect(Storage::disk('public')->exists($this->track->image_path))->toBeTrue();
        expect(Storage::disk('public')->exists($this->track->mp4_path))->toBeTrue();
        
        $this->trackService->startProcessing($this->track, true);
        
        // Verify files were deleted
        expect(Storage::disk('public')->exists('tracks/mp3/' . $this->track->id . '.mp3'))->toBeFalse();
        expect(Storage::disk('public')->exists('tracks/images/' . $this->track->id . '.jpg'))->toBeFalse();
        expect(Storage::disk('public')->exists('tracks/mp4/' . $this->track->id . '.mp4'))->toBeFalse();
    });

    it('handles file deletion errors gracefully', function () {
        // Create a track with completed status
        $this->track->update(['status' => 'completed']);
        
        // Mock Storage to throw exception
        Storage::shouldReceive('disk->exists')->andReturn(true);
        Storage::shouldReceive('disk->delete')->andThrow(new \Exception('Delete failed'));
        
        // Should not throw exception and should still process
        $result = $this->trackService->startProcessing($this->track, true);
        
        expect($result)->toBeTrue();
        
        $updatedTrack = Track::where('id', $this->track->id)->first();
        expect($updatedTrack)->not->toBeNull();
        expect($updatedTrack->status)->toBe('pending');
    });

    it('can bulk start multiple tracks', function () {
        $tracks = Track::factory()->count(3)->create(['status' => 'pending']);
        
        $results = [];
        foreach ($tracks as $track) {
            $results[] = $this->trackService->startProcessing($track);
        }
        
        expect(array_filter($results))->toHaveCount(3); // All should be true
        
        foreach ($tracks as $track) {
            $updatedTrack = Track::where('id', $track->id)->first();
            expect($updatedTrack)->not->toBeNull();
            expect($updatedTrack->status)->toBe('pending');
        }
        
        Queue::assertPushed(ProcessTrack::class, 3);
    });

    it('logs errors appropriately', function () {
        // This would require mocking the Log facade to test logging
        // For now, we'll just ensure the methods don't throw exceptions
        
        $this->track->update(['status' => 'processing']);
        
        // These should not throw exceptions even with invalid states
        expect(fn() => $this->trackService->startProcessing($this->track))->not->toThrow(\Exception::class);
        expect(fn() => $this->trackService->stopProcessing($this->track))->not->toThrow(\Exception::class);
    });

    it('handles track with missing URLs', function () {
        $trackWithoutUrls = Track::factory()->create([
            'status' => 'pending',
            'mp3_url' => null,
            'image_url' => null,
        ]);
        
        $result = $this->trackService->startProcessing($trackWithoutUrls);
        
        // Should still attempt to process (validation happens in the job)
        expect($result)->toBeTrue();
        
        $updatedTrack = Track::where('id', $trackWithoutUrls->id)->first();
        expect($updatedTrack)->not->toBeNull();
        expect($updatedTrack->status)->toBe('pending');
    });
}); 