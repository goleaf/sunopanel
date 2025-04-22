@extends('layouts.app')

@section('title', 'YouTube Uploads')

@section('content')
<div class="container">
    <div class="row">
        <!-- Display success, error, and info messages -->
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info">
                {{ session('info') }}
            </div>
        @endif
    </div>

    <div class="row mt-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">YouTube Upload Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <form action="{{ route('youtube.sync') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">Sync Uploads</button>
                            </form>
                        </div>
                        <div>
                            <button id="refresh-stats-btn" class="btn btn-sm btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Refresh Stats
                            </button>
                        </div>
                    </div>

                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Total YouTube Uploads</span>
                            <span class="badge bg-primary">{{ $totalUploads }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Ready To Upload</span>
                            <span class="badge bg-warning">{{ $tracksReadyToUpload }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Total Views</span>
                            <span id="total-views" class="badge bg-success">{{ number_format($totalViews) }}</span>
                        </li>
                    </ul>
                    
                    <p id="stats-last-updated" class="text-muted mt-2 small">Stats last updated: {{ now()->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Upload Options</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('youtube.upload.form') }}" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-upload"></i> Upload New Track
                    </a>
                    
                    <div class="mt-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="auto-refresh-toggle" checked>
                            <label class="form-check-label" for="auto-refresh-toggle">Auto-refresh stats</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Uploaded Tracks</h5>
                </div>
                <div class="card-body">
                    @if ($tracks->isEmpty())
                        <div class="alert alert-info">
                            No tracks have been uploaded to YouTube yet.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Track</th>
                                        <th>Uploaded</th>
                                        <th>YouTube</th>
                                        <th>Views</th>
                                        <th>Playlist</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tracks as $track)
                                        <tr>
                                            <td>
                                                <a href="{{ route('tracks.show', $track) }}">{{ $track->title }}</a>
                                            </td>
                                            <td>
                                                @if ($track->youtube_uploaded_at)
                                                    {{ $track->youtube_uploaded_at->format('Y-m-d') }}
                                                @else
                                                    <span class="text-muted">Not uploaded</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($track->youtube_video_id)
                                                    <a href="{{ $track->youtube_url }}" target="_blank" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-youtube"></i> View
                                                    </a>
                                                @else
                                                    <span class="text-muted">No link</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($track->youtube_video_id)
                                                    <span class="video-view-count badge bg-secondary" data-video-id="{{ $track->youtube_video_id }}">
                                                        {{ isset($videoStats[$track->youtube_video_id]) ? number_format($videoStats[$track->youtube_video_id]['viewCount']) : 0 }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">0</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($track->youtube_playlist_id)
                                                    <span class="badge bg-info">{{ $track->youtube_playlist_id }}</span>
                                                @else
                                                    <span class="text-muted">None</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex">
                                                    @if ($track->youtube_video_id)
                                                        <form action="{{ route('youtube.toggle-enabled') }}" method="POST" class="me-1">
                                                            @csrf
                                                            <input type="hidden" name="track_id" value="{{ $track->id }}">
                                                            <button type="submit" class="btn btn-sm {{ $track->youtube_enabled ? 'btn-success' : 'btn-outline-success' }}" 
                                                                   title="{{ $track->youtube_enabled ? 'Disable YouTube' : 'Enable YouTube' }}">
                                                                <i class="bi {{ $track->youtube_enabled ? 'bi-check-circle-fill' : 'bi-check-circle' }}"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if (!$track->youtube_video_id && $track->status === 'completed')
                                                        <form action="{{ route('youtube.upload') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="track_id" value="{{ $track->id }}">
                                                            <button type="submit" class="btn btn-sm btn-primary">Upload</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $tracks->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/youtube-stats.js', 'resources/js/youtube-toggle.js'])
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize YouTube Stats Manager
        const statsManager = new YouTubeStatsManager({
            refreshInterval: 300000, // 5 minutes
            autoRefresh: true,
            showNotifications: true
        });
        
        // Handle auto-refresh toggle
        const autoRefreshToggle = document.getElementById('auto-refresh-toggle');
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', function() {
                if (this.checked) {
                    statsManager.startAutoRefresh();
                } else {
                    statsManager.stopAutoRefresh();
                }
            });
        }
        
        // Initial refresh of stats
        statsManager.refreshStats();
    });
</script>
@endpush 