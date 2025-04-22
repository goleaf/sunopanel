@extends('layouts.app')

@section('title', 'YouTube Uploads')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Page header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600 mr-2" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                </svg>
                YouTube Uploads
            </h1>
            <p class="text-sm text-base-content/70 mt-1">Manage your YouTube video uploads and track statistics</p>
        </div>
        <div class="flex space-x-2 mt-4 md:mt-0">
            <form action="{{ route('youtube.sync') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    Sync Uploads
                </button>
            </form>
            <button id="refresh-stats-btn" class="btn btn-outline btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh Stats
            </button>
        </div>
    </div>

    <!-- Display messages -->
    @if(session('success'))
        <div class="alert alert-success mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-6">
            {{ session('error') }}
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info mb-6">
            {{ session('info') }}
        </div>
    @endif

    <!-- Stats and Upload Options -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total Uploads -->
        <div class="card bg-base-100 shadow hover:shadow-md transition-shadow duration-300">
            <div class="card-body p-6">
                <div class="stat">
                    <div class="stat-figure text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="stat-title">Total YouTube Uploads</div>
                    <div class="stat-value text-primary">{{ $totalUploads }}</div>
                    <div class="stat-desc mt-2">Videos on your YouTube channel</div>
                </div>
            </div>
        </div>

        <!-- Ready to Upload -->
        <div class="card bg-base-100 shadow hover:shadow-md transition-shadow duration-300">
            <div class="card-body p-6">
                <div class="stat">
                    <div class="stat-figure text-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">Ready To Upload</div>
                    <div class="stat-value text-warning">{{ $tracksReadyToUpload }}</div>
                    <div class="stat-desc mt-2">
                        <a href="{{ route('youtube.upload.form') }}" class="link link-hover">Upload now</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Views -->
        <div class="card bg-base-100 shadow hover:shadow-md transition-shadow duration-300">
            <div class="card-body p-6">
                <div class="stat">
                    <div class="stat-figure text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <div class="stat-title">Total Views</div>
                    <div id="total-views" class="stat-value text-success">{{ number_format($totalViews) }}</div>
                    <div id="stats-last-updated" class="stat-desc mt-2">Last updated: {{ now()->format('Y-m-d H:i:s') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Options Bar -->
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <div>
            <a href="{{ route('youtube.upload.form') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Upload New Track
            </a>
        </div>
        <div class="flex items-center gap-3">
            <div class="form-control">
                <label class="cursor-pointer label flex justify-start gap-2 p-0">
                    <span class="label-text">Auto-refresh stats</span> 
                    <input type="checkbox" id="auto-refresh-toggle" class="toggle toggle-sm toggle-info" checked />
                </label>
            </div>
        </div>
    </div>

    <!-- Tracks Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body p-0">
            <div class="p-4 border-b border-base-200">
                <h2 class="card-title text-lg">Uploaded Tracks</h2>
            </div>
            
            @if ($tracks->isEmpty())
                <div class="p-6 flex flex-col items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-base-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                    </svg>
                    <p class="text-base-content/70 text-lg">No tracks have been uploaded to YouTube yet.</p>
                    <a href="{{ route('youtube.upload.form') }}" class="btn btn-primary btn-sm mt-4">Upload Your First Track</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Track</th>
                                <th>Uploaded</th>
                                <th>YouTube</th>
                                <th>Views</th>
                                <th>Playlist</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tracks as $track)
                                <tr class="hover" data-track-id="{{ $track->id }}">
                                    <td>
                                        <div class="flex items-center space-x-3">
                                            <div class="avatar">
                                                <div class="mask mask-squircle w-10 h-10">
                                                    @if ($track->image_storage_url)
                                                        <img src="{{ $track->image_storage_url }}" alt="{{ $track->title }}">
                                                    @else
                                                        <div class="bg-base-300 w-full h-full flex items-center justify-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div>
                                                <a href="{{ route('tracks.show', $track) }}" class="font-medium hover:underline">{{ $track->title }}</a>
                                                <div class="text-sm opacity-70">ID: {{ $track->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($track->youtube_uploaded_at)
                                            <div class="tooltip" data-tip="{{ $track->youtube_uploaded_at }}">
                                                {{ $track->youtube_uploaded_at->diffForHumans() }}
                                            </div>
                                        @else
                                            <span class="text-base-content/50">Not uploaded</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($track->youtube_video_id)
                                            <a href="{{ $track->youtube_url }}" target="_blank" class="btn btn-sm btn-error gap-1 tooltip" data-tip="View on YouTube">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                                                </svg>
                                                View
                                            </a>
                                        @else
                                            <span class="badge badge-ghost">No link</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($track->youtube_video_id)
                                            <div class="flex items-center">
                                                <span class="video-view-count badge badge-sm badge-secondary mr-1" data-video-id="{{ $track->youtube_video_id }}">
                                                    {{ isset($videoStats[$track->youtube_video_id]) ? number_format($videoStats[$track->youtube_video_id]['viewCount']) : 0 }}
                                                </span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </div>
                                        @else
                                            <span class="badge badge-ghost">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($track->youtube_playlist_id)
                                            <a href="{{ $track->youtube_playlist_url }}" target="_blank" class="link link-hover link-primary text-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                                </svg>
                                                View Playlist
                                            </a>
                                        @else
                                            <span class="text-base-content/50 text-sm">None</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="flex justify-end space-x-1">
                                            @if ($track->youtube_video_id)
                                                <form action="{{ route('youtube.toggle-enabled') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="track_id" value="{{ $track->id }}">
                                                    <button type="submit" class="btn btn-sm btn-circle {{ $track->youtube_enabled ? 'btn-success' : 'btn-outline btn-success' }}" 
                                                            title="{{ $track->youtube_enabled ? 'Disable YouTube' : 'Enable YouTube' }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif

                                            @if (!$track->youtube_video_id && $track->status === 'completed')
                                                <form action="{{ route('youtube.upload') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="track_id" value="{{ $track->id }}">
                                                    <button type="submit" class="btn btn-sm btn-primary btn-circle" title="Upload to YouTube">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif

                                            <a href="{{ route('tracks.show', $track) }}" class="btn btn-sm btn-circle btn-ghost" title="View Details">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-4">
                    {{ $tracks->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize YouTube Stats Manager
        const statsManager = {
            refreshInterval: 300000, // 5 minutes
            autoRefresh: true,
            timerId: null,
            
            startAutoRefresh() {
                this.autoRefresh = true;
                if (this.timerId === null) {
                    this.timerId = setInterval(() => this.refreshStats(), this.refreshInterval);
                }
            },
            
            stopAutoRefresh() {
                this.autoRefresh = false;
                if (this.timerId !== null) {
                    clearInterval(this.timerId);
                    this.timerId = null;
                }
            },
            
            refreshStats() {
                const refreshBtn = document.getElementById('refresh-stats-btn');
                if (refreshBtn) {
                    refreshBtn.classList.add('loading');
                }
                
                // Simulate refresh (replace with actual fetch call)
                fetch('/youtube/refresh-stats', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.videoStats) {
                        // Update view counts
                        const viewCountElements = document.querySelectorAll('.video-view-count');
                        viewCountElements.forEach(el => {
                            const videoId = el.dataset.videoId;
                            if (videoId && data.videoStats[videoId]) {
                                el.textContent = Number(data.videoStats[videoId].viewCount).toLocaleString();
                            }
                        });
                        
                        // Update total views
                        const totalViewsEl = document.getElementById('total-views');
                        if (totalViewsEl && data.totalViews !== undefined) {
                            totalViewsEl.textContent = Number(data.totalViews).toLocaleString();
                        }
                        
                        // Update last updated timestamp
                        const statsLastUpdatedEl = document.getElementById('stats-last-updated');
                        if (statsLastUpdatedEl) {
                            statsLastUpdatedEl.textContent = `Last updated: ${new Date().toLocaleString()}`;
                        }
                    }
                    
                    // Show toast notification
                    const toastContainer = document.createElement('div');
                    toastContainer.className = 'toast toast-end';
                    toastContainer.innerHTML = `
                        <div class="alert alert-info">
                            <span>Statistics refreshed successfully.</span>
                        </div>
                    `;
                    document.body.appendChild(toastContainer);
                    
                    setTimeout(() => {
                        toastContainer.remove();
                    }, 3000);
                })
                .catch(error => {
                    console.error('Error refreshing stats:', error);
                    
                    // Show error toast
                    const toastContainer = document.createElement('div');
                    toastContainer.className = 'toast toast-end';
                    toastContainer.innerHTML = `
                        <div class="alert alert-error">
                            <span>Failed to refresh statistics.</span>
                        </div>
                    `;
                    document.body.appendChild(toastContainer);
                    
                    setTimeout(() => {
                        toastContainer.remove();
                    }, 3000);
                })
                .finally(() => {
                    if (refreshBtn) {
                        refreshBtn.classList.remove('loading');
                    }
                });
            }
        };
        
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
        
        // Handle refresh button
        const refreshBtn = document.getElementById('refresh-stats-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                statsManager.refreshStats();
            });
        }
        
        // Initial refresh and auto-refresh setup
        if (autoRefreshToggle && autoRefreshToggle.checked) {
            statsManager.startAutoRefresh();
        }
    });
</script>
@endpush 