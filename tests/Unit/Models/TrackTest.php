<?php

declare(strict_types=1);

use App\Models\Track;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->track = Track::factory()->create([
        'title' => 'Test Track',
        'mp3_url' => 'https://example.com/test.mp3',
        'image_url' => 'https://example.com/test.jpg',
        'status' => 'pending',
        'progress' => 0,
        'youtube_enabled' => true,
        'mp3_path' => null,
        'image_path' => null,
        'mp4_path' => null,
        'youtube_video_id' => null,
    ]);
});

describe('Track Model', function () {
    it('can be created with valid data', function () {
        expect($this->track)->toBeInstanceOf(Track::class)
            ->and($this->track->title)->toBe('Test Track')
            ->and($this->track->status)->toBe('pending')
            ->and($this->track->progress)->toBe(0)
            ->and($this->track->youtube_enabled)->toBeTrue();
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'title', 'suno_id', 'mp3_url', 'image_url', 'mp3_path', 'image_path',
            'mp4_path', 'genres_string', 'status', 'progress', 'error_message',
            'youtube_video_id', 'youtube_playlist_id', 'youtube_uploaded_at',
            'youtube_views', 'youtube_stats_updated_at', 'youtube_enabled',
            'youtube_view_count', 'youtube_like_count', 'youtube_dislike_count',
            'youtube_comment_count', 'youtube_favorite_count', 'youtube_duration',
            'youtube_definition', 'youtube_caption', 'youtube_licensed_content',
            'youtube_privacy_status', 'youtube_published_at', 'youtube_analytics_updated_at',
        ];

        expect($this->track->getFillable())->toEqual($fillable);
    });

    it('casts attributes correctly', function () {
        $track = Track::factory()->create([
            'progress' => '50',
            'youtube_uploaded_at' => '2024-01-01 12:00:00',
            'youtube_stats_updated_at' => '2024-01-01 13:00:00',
            'youtube_views' => '1000',
            'youtube_enabled' => '1',
        ]);

        expect($track->progress)->toBeInt()
            ->and($track->youtube_uploaded_at)->toBeInstanceOf(Carbon\Carbon::class)
            ->and($track->youtube_stats_updated_at)->toBeInstanceOf(Carbon\Carbon::class)
            ->and($track->youtube_views)->toBeInt()
            ->and($track->youtube_enabled)->toBeBool();
    });

    it('has valid status values', function () {
        $expectedStatuses = ['pending', 'processing', 'completed', 'failed', 'stopped'];
        expect(Track::$statuses)->toEqual($expectedStatuses);
    });
});

describe('Track Relationships', function () {
    it('has genres relationship defined', function () {
        expect($this->track->genres())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    it('can get empty genres list when no genres attached', function () {
        expect($this->track->genres_list)->toBe('');
    });
});

describe('Track Scopes', function () {
    beforeEach(function () {
        // Clear existing tracks to have predictable counts
        Track::query()->delete();
        
        // Create test tracks with explicit status values
        Track::factory()->create(['status' => 'pending', 'youtube_video_id' => null, 'youtube_enabled' => true]);
        Track::factory()->create(['status' => 'completed', 'youtube_video_id' => null, 'youtube_enabled' => true]);
        Track::factory()->create(['status' => 'failed', 'youtube_video_id' => null, 'youtube_enabled' => true]);
        Track::factory()->create(['status' => 'processing', 'youtube_video_id' => null, 'youtube_enabled' => true]);
        Track::factory()->create(['status' => 'completed', 'youtube_video_id' => 'abc123', 'youtube_enabled' => true]);
        Track::factory()->create(['status' => 'completed', 'youtube_video_id' => null, 'youtube_enabled' => true]);
        Track::factory()->create(['status' => 'completed', 'youtube_video_id' => null, 'youtube_enabled' => true]);
        Track::factory()->create(['status' => 'completed', 'youtube_video_id' => null, 'youtube_enabled' => false]);
    });

    it('can filter by completed status', function () {
        $completedTracks = Track::completed()->get();
        expect($completedTracks)->toHaveCount(5) // 5 tracks with completed status
            ->and($completedTracks->first()->status)->toBe('completed');
    });

    it('can filter by failed status', function () {
        $failedTracks = Track::failed()->get();
        expect($failedTracks)->toHaveCount(1)
            ->and($failedTracks->first()->status)->toBe('failed');
    });

    it('can filter by processing status', function () {
        $processingTracks = Track::processing()->get();
        expect($processingTracks)->toHaveCount(1)
            ->and($processingTracks->first()->status)->toBe('processing');
    });

    it('can filter by status', function () {
        $pendingTracks = Track::withStatus('pending')->get();
        expect($pendingTracks)->toHaveCount(1)
            ->and($pendingTracks->first()->status)->toBe('pending');
    });

    it('can filter uploaded to youtube tracks', function () {
        $uploadedTracks = Track::uploadedToYoutube()->get();
        expect($uploadedTracks)->toHaveCount(1)
            ->and($uploadedTracks->first()->youtube_video_id)->toBe('abc123');
    });

    it('can filter not uploaded to youtube tracks', function () {
        $notUploadedTracks = Track::notUploadedToYoutube()->get();
        expect($notUploadedTracks)->toHaveCount(7); // All except the one with youtube_video_id
    });

    it('can filter youtube enabled tracks', function () {
        $enabledTracks = Track::youtubeEnabled()->get();
        expect($enabledTracks)->toHaveCount(7); // 7 tracks with youtube_enabled = true
    });

    it('can filter youtube disabled tracks', function () {
        $disabledTracks = Track::youtubeDisabled()->get();
        expect($disabledTracks)->toHaveCount(1); // 1 track with youtube_enabled = false
    });
});

describe('Track Methods', function () {
    it('can toggle youtube enabled status', function () {
        expect($this->track->youtube_enabled)->toBeTrue();

        $this->track->toggleYoutubeEnabled();
        expect($this->track->youtube_enabled)->toBeFalse();

        $this->track->toggleYoutubeEnabled();
        expect($this->track->youtube_enabled)->toBeTrue();
    });

    it('can get youtube url when video id exists', function () {
        $this->track->update(['youtube_video_id' => 'abc123']);
        expect($this->track->youtube_url)->toBe('https://www.youtube.com/watch?v=abc123');
    });

    it('returns null for youtube url when no video id', function () {
        expect($this->track->youtube_url)->toBeNull();
    });

    it('can check if uploaded to youtube', function () {
        expect($this->track->isUploadedToYoutube())->toBeFalse();

        $this->track->update(['youtube_video_id' => 'abc123']);
        expect($this->track->isUploadedToYoutube())->toBeTrue();
    });

    it('can get file paths', function () {
        $this->track->update([
            'mp3_path' => 'tracks/test.mp3',
            'image_path' => 'images/test.jpg',
            'mp4_path' => 'videos/test.mp4',
        ]);

        expect($this->track->mp3_file_path)->toBe(storage_path('app/public/tracks/test.mp3'))
            ->and($this->track->image_file_path)->toBe(storage_path('app/public/images/test.jpg'))
            ->and($this->track->mp4_file_path)->toBe(storage_path('app/public/videos/test.mp4'));
    });

    it('returns null for file paths when not set', function () {
        expect($this->track->mp3_file_path)->toBeNull()
            ->and($this->track->image_file_path)->toBeNull()
            ->and($this->track->mp4_file_path)->toBeNull();
    });
}); 