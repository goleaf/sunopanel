@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Section header with stats -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 gap-4">
        <div class="w-full md:w-auto">
            <div class="stats shadow mb-4">
                <div class="stat">
                    <div class="stat-title">Total</div>
                    <div class="stat-value" data-count="total">{{ $totalTracks }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Processing</div>
                    <div class="stat-value text-warning" data-count="processing">{{ $processingTracks }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Pending</div>
                    <div class="stat-value text-info" data-count="pending">{{ $pendingTracks }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Completed</div>
                    <div class="stat-value text-success" data-count="completed">{{ $completedTracks }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Failed</div>
                    <div class="stat-value text-error" data-count="failed">{{ $failedTracks }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Stopped</div>
                    <div class="stat-value text-warning" data-count="stopped">{{ $stoppedTracks }}</div>
                </div>
            </div>

            <!-- search bar and filter buttons -->
            <div class="flex flex-wrap gap-2 mb-4">
                <form action="{{ route('tracks.index') }}" method="GET" class="w-full md:w-auto flex gap-2 flex-wrap items-center">
                    <div class="input-group">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tracks..." class="input input-bordered w-full max-w-xs">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Hidden fields to preserve genre filter -->
                    @if(request('genre'))
                    <input type="hidden" name="genre" value="{{ request('genre') }}">
                    @endif
                    
                    <div class="join">
                        <button type="submit" name="status" value="" class="btn join-item {{ !request('status') ? 'btn-primary' : 'btn-outline' }}">All</button>
                        <button type="submit" name="status" value="processing" class="btn join-item {{ request('status') == 'processing' ? 'btn-primary' : 'btn-outline' }}">Processing</button>
                        <button type="submit" name="status" value="pending" class="btn join-item {{ request('status') == 'pending' ? 'btn-primary' : 'btn-outline' }}">Pending</button>
                        <button type="submit" name="status" value="completed" class="btn join-item {{ request('status') == 'completed' ? 'btn-primary' : 'btn-outline' }}">Completed</button>
                        <button type="submit" name="status" value="failed" class="btn join-item {{ request('status') == 'failed' ? 'btn-primary' : 'btn-outline' }}">Failed</button>
                        <button type="submit" name="status" value="stopped" class="btn join-item {{ request('status') == 'stopped' ? 'btn-primary' : 'btn-outline' }}">Stopped</button>
                    </div>
                </form>
            </div>

            <!-- Bulk action buttons -->
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div class="flex flex-wrap gap-2">
                    <button id="start-all-tracks" class="btn btn-success btn-sm">
                        <span class="mr-1">▶</span> Start All
                    </button>
                    <button id="stop-all-tracks" class="btn btn-error btn-sm">
                        <span class="mr-1">■</span> Stop All
                    </button>
                    @if($failedTracks > 0)
                    <button id="retry-all-tracks" class="btn btn-warning btn-sm">
                        <span class="mr-1">↻</span> Retry Failed
                    </button>
                    @endif
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <div class="form-control">
                        <label class="cursor-pointer label flex justify-start gap-2 p-0">
                            <span class="label-text">Show Completed</span> 
                            <input type="checkbox" id="toggle-completed" class="toggle toggle-sm toggle-success" checked />
                        </label>
                    </div>
                    <div class="form-control">
                        <label class="cursor-pointer label flex justify-start gap-2 p-0">
                            <span class="label-text">Auto-Refresh</span> 
                            <input type="checkbox" id="toggle-auto-refresh" class="toggle toggle-sm toggle-info" checked />
                        </label>
                    </div>
                    <button id="refresh-now" class="btn btn-outline btn-sm" title="Refresh Now">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-6">
        {{ session('success') }}
    </div>
    @endif

    @if(session('info'))
    <div class="alert alert-info mb-6">
        {{ session('info') }}
    </div>
    @endif

    <!-- Tracks table -->
    <div class="overflow-x-auto bg-base-100 rounded-lg shadow">
        <table class="table table-zebra w-full">
            <thead>
                <tr>
                    <th>Track</th>
                    <th>Genres</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th class="w-24">YouTube</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tracks as $track)
                <tr data-track-id="{{ $track->id }}" class="status-{{ $track->status }}">
                    <td>
                        <div class="flex items-center space-x-3">
                            <div class="avatar">
                                <div class="mask mask-squircle w-12 h-12">
                                    @if($track->image_path)
                                    <img src="{{ $track->image_storage_url }}" alt="{{ $track->title }}">
                                    @else
                                    <div class="bg-base-300 w-full h-full flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                        </svg>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('tracks.show', $track) }}" class="font-bold hover:text-primary">{{ $track->title }}</a>
                                <div class="text-sm opacity-50">Added {{ $track->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            @foreach($track->genres as $genre)
                            <a href="{{ route('genres.show', $genre) }}" class="badge badge-sm badge-primary">{{ $genre->name }}</a>
                            @endforeach
                        </div>
                    </td>
                    <td class="track-status" data-status="{{ $track->status }}">
                        @if($track->status === 'completed')
                        <span class="badge badge-sm badge-success">Completed</span>
                        @elseif($track->status === 'processing')
                        <span class="badge badge-sm badge-warning">Processing</span>
                        @elseif($track->status === 'failed')
                        <span class="badge badge-sm badge-error">Failed</span>
                        @elseif($track->status === 'stopped')
                        <span class="badge badge-sm badge-warning">Stopped</span>
                        @else
                        <span class="badge badge-sm badge-info">Pending</span>
                        @endif
                    </td>
                    <td class="track-progress" data-progress="{{ $track->progress }}">
                        @if($track->status === 'processing')
                        <div class="flex items-center">
                            <progress class="progress progress-xs progress-warning flex-grow mr-1" value="{{ $track->progress }}" max="100"></progress>
                            <span class="text-xs">{{ $track->progress }}%</span>
                        </div>
                        @elseif($track->status === 'completed')
                        <div class="flex items-center">
                            <progress class="progress progress-xs progress-success flex-grow mr-1" value="100" max="100"></progress>
                            <span class="text-xs">100%</span>
                        </div>
                        @elseif($track->status === 'failed')
                        <div class="tooltip w-full" data-tip="{{ $track->error_message }}">
                            <progress class="progress progress-xs progress-error w-full" value="100" max="100"></progress>
                        </div>
                        @elseif($track->status === 'stopped')
                        <div class="tooltip w-full" data-tip="Processing was manually stopped">
                            <progress class="progress progress-xs progress-warning w-full" value="{{ $track->progress }}" max="100"></progress>
                        </div>
                        @else
                        <div class="flex items-center">
                            <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                            <span class="text-xs">0%</span>
                        </div>
                        @endif
                    </td>
                    <td class="w-24">
                        @if($track->youtube_uploaded)
                            <div class="flex items-center justify-center">
                                <a href="{{ $track->youtube_video_url }}" target="_blank" class="tooltip" data-tip="View on YouTube">
                                    <span class="badge badge-sm badge-success">Uploaded</span>
                                </a>
                                <form action="{{ route('tracks.toggle-youtube-status', $track) }}" method="POST" class="ml-2">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-circle btn-ghost tooltip" data-tip="Mark as not uploaded">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="flex items-center justify-center">
                                <span class="badge badge-sm badge-ghost">Not uploaded</span>
                                <form action="{{ route('tracks.toggle-youtube-status', $track) }}" method="POST" class="ml-2">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-circle btn-ghost tooltip" data-tip="Mark as uploaded">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="flex space-x-1 justify-end">
                            <a href="{{ route('tracks.show', $track) }}" class="btn btn-sm btn-circle btn-ghost" title="View Details">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            
                            @if(in_array($track->status, ['failed', 'stopped']))
                            <button type="button" class="btn btn-sm btn-circle btn-success start-track" data-track-id="{{ $track->id }}" title="Start Processing">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                </svg>
                            </button>
                            @endif
                            
                            @if(in_array($track->status, ['processing', 'pending']))
                            <button type="button" class="btn btn-sm btn-circle btn-error stop-track" data-track-id="{{ $track->id }}" title="Stop Processing">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18h12a1 1 0 001-1V7a1 1 0 00-1-1H6a1 1 0 00-1 1v10a1 1 0 001 1z" />
                                </svg>
                            </button>
                            @endif
                            
                            @if($track->status === 'failed')
                            <button type="button" class="btn btn-sm btn-circle btn-warning retry-track" data-track-id="{{ $track->id }}" title="Retry Processing">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                            @endif
                            
                            @if($track->status === 'completed')
                            <button type="button" class="btn btn-sm btn-circle btn-warning redownload-track" data-track-id="{{ $track->id }}" title="Redownload and Process Again">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </button>
                            @endif
                            
                            <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-circle btn-error" title="Delete Track">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="flex flex-col items-center justify-center p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                            </svg>
                            <p class="text-gray-500">No tracks found</p>
                            <a href="{{ route('home.index') }}" class="btn btn-primary btn-sm mt-3">Add Tracks</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $tracks->links() }}
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="fixed bottom-4 right-4 p-4 rounded shadow-lg transform transition-transform duration-300 ease-in-out translate-y-full hidden">
    <div class="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info shrink-0 w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span id="toast-message"></span>
    </div>
</div>

<!-- CSRF Token for API requests -->
<meta name="csrf-token" content="{{ csrf_token() }}">

@vite('resources/js/app.js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize track status updater if there are tracks to monitor
    const trackRows = document.querySelectorAll('[data-track-id]');
    if (trackRows.length > 0) {
        // Create a single global status updater with a faster interval
        window.trackStatusUpdater = new TrackStatusAPI({
            interval: 2000,
            autoReload: true,
            reloadInterval: 60000, // 1 minute auto-reload
            hideCompleted: false,
            onUpdate: function(tracks) {
                console.log(`Updated ${tracks.length} tracks`);
                updateStatusCounts();
            }
        });
        
        // Register ALL tracks for monitoring, regardless of status
        trackRows.forEach(row => {
            const trackId = row.getAttribute('data-track-id');
            const statusCell = row.querySelector('.track-status');
            const progressCell = row.querySelector('.track-progress');
            
            // Set correct row status class
            if (statusCell) {
                const status = statusCell.dataset.status;
                if (status) {
                    row.classList.add(`status-${status}`);
                }
            }
            
            // Watch all tracks, not just processing/pending
            window.trackStatusUpdater.watchTrack(trackId, {
                status: statusCell,
                progress: progressCell
            });
        });
        
        // Start the status updater
        window.trackStatusUpdater.start();
        
        // Set up the visibility toggles
        const toggleCompletedEl = document.getElementById('toggle-completed');
        if (toggleCompletedEl) {
            toggleCompletedEl.addEventListener('change', function() {
                if (window.trackStatusUpdater) {
                    window.trackStatusUpdater.visibilityFilters.completed = this.checked;
                    window.trackStatusUpdater.applyVisibilityFilters();
                    
                    // Show toast notification
                    window.showToast(
                        this.checked ? 'Showing completed tracks' : 'Hiding completed tracks', 
                        'info'
                    );
                }
            });
        }
        
        // Set up auto-refresh toggle
        const toggleAutoRefreshEl = document.getElementById('toggle-auto-refresh');
        if (toggleAutoRefreshEl) {
            toggleAutoRefreshEl.addEventListener('change', function() {
                if (window.trackStatusUpdater) {
                    window.trackStatusUpdater.options.autoReload = this.checked;
                    
                    // Clear existing timer if disabling
                    if (!this.checked && window.trackStatusUpdater.reloadTimer) {
                        clearInterval(window.trackStatusUpdater.reloadTimer);
                        window.trackStatusUpdater.reloadTimer = null;
                    } else if (this.checked && !window.trackStatusUpdater.reloadTimer) {
                        // Restart timer if enabling
                        window.trackStatusUpdater.reloadTimer = setInterval(() => {
                            if (window.trackStatusUpdater.checkIfReloadNeeded()) {
                                window.location.reload();
                            }
                        }, window.trackStatusUpdater.options.reloadInterval);
                    }
                    
                    // Show toast notification
                    window.showToast(
                        this.checked ? 'Auto-refresh enabled' : 'Auto-refresh disabled', 
                        'info'
                    );
                }
            });
        }
        
        // Set up refresh-now button
        const refreshNowEl = document.getElementById('refresh-now');
        if (refreshNowEl) {
            refreshNowEl.addEventListener('click', function() {
                window.location.reload();
            });
        }
        
        // Update status counts initially
        updateStatusCounts();
    }
    
    // Function to update status counts
    function updateStatusCounts() {
        if (window.trackStatusUpdater) {
            const counts = window.trackStatusUpdater.countTracksByStatus();
            const countElements = {
                total: document.querySelector('.stat-value[data-count="total"]'),
                completed: document.querySelector('.stat-value[data-count="completed"]'),
                processing: document.querySelector('.stat-value[data-count="processing"]'),
                pending: document.querySelector('.stat-value[data-count="pending"]'),
                failed: document.querySelector('.stat-value[data-count="failed"]'),
                stopped: document.querySelector('.stat-value[data-count="stopped"]')
            };
            
            // Update the count elements
            Object.keys(countElements).forEach(status => {
                if (countElements[status] && counts[status] !== undefined) {
                    countElements[status].textContent = counts[status];
                }
            });
        }
    }
    
    // Function to show a toast notification
    window.showToast = function(message, type = 'info') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        
        // Set the message
        toastMessage.textContent = message;
        
        // Set the alert type
        toast.querySelector('.alert').className = `alert alert-${type}`;
        
        // Show the toast
        toast.classList.remove('hidden', 'translate-y-full');
        toast.classList.add('translate-y-0');
        
        // Hide the toast after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-y-full');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 300);
        }, 3000);
    };
    
    // Setup individual action buttons
    
    // Start track processing
    document.querySelectorAll('.start-track').forEach(button => {
        button.addEventListener('click', async function() {
            const trackId = this.getAttribute('data-track-id');
            
            try {
                this.classList.add('loading');
                const result = await TrackStatusAPI.startTrack(trackId);
                
                if (result.success) {
                    const row = document.querySelector(`[data-track-id="${trackId}"]`);
                    const statusCell = row.querySelector('.track-status');
                    const progressCell = row.querySelector('.track-progress');
                    
                    statusCell.innerHTML = '<span class="badge badge-sm badge-info">Pending</span>';
                    progressCell.innerHTML = `
                        <div class="flex items-center">
                            <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                            <span class="text-xs">0%</span>
                        </div>
                    `;
                    
                    // Update buttons
                    updateTrackActionButtons(row, 'pending');
                    
                    // Add to status updater
                    const statusUpdater = new TrackStatusAPI({ interval: 3000 });
                    statusUpdater.watchTrack(trackId, {
                        status: statusCell,
                        progress: progressCell
                    });
                    statusUpdater.start();
                    
                    window.showToast('Track processing started', 'success');
                }
            } catch (error) {
                console.error('Failed to start processing:', error);
                window.showToast('Failed to start processing: ' + error.message, 'error');
            } finally {
                this.classList.remove('loading');
            }
        });
    });
    
    // Stop track processing
    document.querySelectorAll('.stop-track').forEach(button => {
        button.addEventListener('click', async function() {
            const trackId = this.getAttribute('data-track-id');
            
            try {
                this.classList.add('loading');
                const result = await TrackStatusAPI.stopTrack(trackId);
                
                if (result.success) {
                    const row = document.querySelector(`[data-track-id="${trackId}"]`);
                    const statusCell = row.querySelector('.track-status');
                    const progressCell = row.querySelector('.track-progress');
                    
                    statusCell.innerHTML = '<span class="badge badge-sm badge-warning">Stopped</span>';
                    progressCell.innerHTML = `
                        <div class="tooltip w-full" data-tip="Processing was manually stopped">
                            <progress class="progress progress-xs progress-warning w-full" value="${progressCell.dataset.progress || 0}" max="100"></progress>
                        </div>
                    `;
                    
                    // Update buttons
                    updateTrackActionButtons(row, 'stopped');
                    
                    window.showToast('Track processing stopped', 'warning');
                }
            } catch (error) {
                console.error('Failed to stop processing:', error);
                window.showToast('Failed to stop processing: ' + error.message, 'error');
            } finally {
                this.classList.remove('loading');
            }
        });
    });
    
    // Retry failed track
    document.querySelectorAll('.retry-track').forEach(button => {
        button.addEventListener('click', async function() {
            const trackId = this.getAttribute('data-track-id');
            
            try {
                this.classList.add('loading');
                const result = await TrackStatusAPI.retryTrack(trackId);
                
                if (result.success) {
                    const row = document.querySelector(`[data-track-id="${trackId}"]`);
                    const statusCell = row.querySelector('.track-status');
                    const progressCell = row.querySelector('.track-progress');
                    
                    statusCell.innerHTML = '<span class="badge badge-sm badge-info">Pending</span>';
                    progressCell.innerHTML = `
                        <div class="flex items-center">
                            <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                            <span class="text-xs">0%</span>
                        </div>
                    `;
                    
                    // Update buttons
                    updateTrackActionButtons(row, 'pending');
                    
                    // Add to status updater
                    const statusUpdater = new TrackStatusAPI({ interval: 3000 });
                    statusUpdater.watchTrack(trackId, {
                        status: statusCell,
                        progress: progressCell
                    });
                    statusUpdater.start();
                    
                    window.showToast('Track processing retried', 'success');
                }
            } catch (error) {
                console.error('Failed to retry processing:', error);
                window.showToast('Failed to retry processing: ' + error.message, 'error');
            } finally {
                this.classList.remove('loading');
            }
        });
    });
    
    // Redownload completed track
    document.querySelectorAll('.redownload-track').forEach(button => {
        button.addEventListener('click', async function() {
            const trackId = this.getAttribute('data-track-id');
            
            try {
                this.classList.add('loading');
                
                // Confirm action
                if (!confirm("This will redownload the track's files and process it again. Continue?")) {
                    this.classList.remove('loading');
                    return;
                }
                
                // Call the start route which will handle redownload
                const response = await fetch(`/api/tracks/${trackId}/start`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    // Send force_redownload flag
                    body: JSON.stringify({ force_redownload: true })
                });
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to redownload track');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    const row = document.querySelector(`[data-track-id="${trackId}"]`);
                    const statusCell = row.querySelector('.track-status');
                    const progressCell = row.querySelector('.track-progress');
                    
                    statusCell.innerHTML = '<span class="badge badge-sm badge-info">Pending</span>';
                    progressCell.innerHTML = `
                        <div class="flex items-center">
                            <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                            <span class="text-xs">0%</span>
                        </div>
                    `;
                    
                    // Update buttons
                    updateTrackActionButtons(row, 'pending');
                    
                    window.showToast('Track redownload started', 'success');
                }
            } catch (error) {
                console.error('Failed to redownload track:', error);
                window.showToast('Failed to redownload track: ' + error.message, 'error');
            } finally {
                this.classList.remove('loading');
            }
        });
    });
    
    // Bulk action buttons
    
    // Start all tracks
    document.getElementById('start-all-tracks').addEventListener('click', async function() {
        try {
            this.classList.add('loading');
            console.log('Starting all tracks...');
            
            // Get current filter values
            const urlParams = new URLSearchParams(window.location.search);
            const currentStatus = urlParams.get('status') || '';
            const searchTerm = urlParams.get('search') || '';
            const genre = urlParams.get('genre') || '';
            
            // Prepare data for API call
            const requestData = {
                filter_status: currentStatus, 
                search: searchTerm,
                filter_genres: genre ? [genre] : []
            };
            
            console.log('Request data:', requestData);
            
            // Make the API call
            const response = await fetch('/api/tracks/start-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(requestData)
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Failed to start tracks: ${response.statusText}`);
            }
            
            const result = await response.json();
            console.log('Start all tracks response:', result);
            
            if (result.success) {
                window.showToast(`${result.count} tracks queued for processing`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                window.showToast(result.message || 'No tracks were started', 'warning');
            }
        } catch (error) {
            console.error('Failed to start all tracks:', error);
            window.showToast('Failed to start all tracks: ' + error.message, 'error');
        } finally {
            this.classList.remove('loading');
        }
    });
    
    // Stop all tracks
    document.getElementById('stop-all-tracks').addEventListener('click', async function() {
        try {
            this.classList.add('loading');
            console.log('Stopping all tracks...');
            
            // Get current filter values
            const urlParams = new URLSearchParams(window.location.search);
            const currentStatus = urlParams.get('status') || '';
            const searchTerm = urlParams.get('search') || '';
            const genre = urlParams.get('genre') || '';
            
            // Prepare data for API call
            const requestData = {
                filter_status: currentStatus, 
                search: searchTerm,
                filter_genres: genre ? [genre] : []
            };
            
            console.log('Request data:', requestData);
            
            // Make the API call
            const response = await fetch('/api/tracks/stop-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(requestData)
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Failed to stop tracks: ${response.statusText}`);
            }
            
            const result = await response.json();
            console.log('Stop all tracks response:', result);
            
            if (result.success) {
                window.showToast(`${result.count} tracks stopped`, 'warning');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                window.showToast(result.message || 'No tracks were stopped', 'warning');
            }
        } catch (error) {
            console.error('Failed to stop all tracks:', error);
            window.showToast('Failed to stop all tracks: ' + error.message, 'error');
        } finally {
            this.classList.remove('loading');
        }
    });
    
    // Retry all failed tracks
    const retryAllButton = document.getElementById('retry-all-tracks');
    if (retryAllButton) {
        retryAllButton.addEventListener('click', async function() {
            try {
                this.classList.add('loading');
                console.log('Retrying all failed tracks...');
                
                // Get current filter values
                const urlParams = new URLSearchParams(window.location.search);
                const currentStatus = urlParams.get('status') || 'failed';
                const searchTerm = urlParams.get('search') || '';
                const genre = urlParams.get('genre') || '';
                
                // Prepare data for API call
                const requestData = {
                    filter_status: currentStatus,
                    search: searchTerm,
                    filter_genres: genre ? [genre] : []
                };
                
                console.log('Request data:', requestData);
                
                // Make the API call
                const response = await fetch('/api/tracks/retry-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify(requestData)
                });
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `Failed to retry all tracks: ${response.statusText}`);
                }
                
                const result = await response.json();
                console.log('Retry all tracks response:', result);
                
                if (result.success) {
                    window.showToast(`${result.count} failed tracks requeued`, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    window.showToast(result.message || 'No tracks were retried', 'warning');
                }
            } catch (error) {
                console.error('Failed to retry all tracks:', error);
                window.showToast('Failed to retry all tracks: ' + error.message, 'error');
            } finally {
                this.classList.remove('loading');
            }
        });
    }
    
    // Helper function to update the action buttons based on status
    function updateTrackActionButtons(row, newStatus) {
        const actionsCell = row.querySelector('td:last-child > div');
        const trackId = row.getAttribute('data-track-id');
        
        // Clear existing action buttons (except View and Delete)
        const buttons = actionsCell.querySelectorAll('button:not([type="submit"])');
        buttons.forEach(button => button.remove());
        
        // Add the appropriate buttons based on the new status
        if (['failed', 'stopped'].includes(newStatus)) {
            // Add start button
            const startButton = document.createElement('button');
            startButton.className = 'btn btn-sm btn-circle btn-success start-track';
            startButton.setAttribute('data-track-id', trackId);
            startButton.setAttribute('title', 'Start Processing');
            startButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                </svg>
            `;
            startButton.addEventListener('click', async function() {
                // ... existing start track event handler ...
            });
            
            // Insert after view button
            const viewButton = actionsCell.querySelector('a');
            viewButton.after(startButton);
            
            // Add retry button if failed
            if (newStatus === 'failed') {
                const retryButton = document.createElement('button');
                retryButton.className = 'btn btn-sm btn-circle btn-warning retry-track';
                retryButton.setAttribute('data-track-id', trackId);
                retryButton.setAttribute('title', 'Retry Processing');
                retryButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                `;
                startButton.after(retryButton);
            }
        } else if (['processing', 'pending'].includes(newStatus)) {
            // Add stop button
            const stopButton = document.createElement('button');
            stopButton.className = 'btn btn-sm btn-circle btn-error stop-track';
            stopButton.setAttribute('data-track-id', trackId);
            stopButton.setAttribute('title', 'Stop Processing');
            stopButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18h12a1 1 0 001-1V7a1 1 0 00-1-1H6a1 1 0 00-1 1v10a1 1 0 001 1z" />
                </svg>
            `;
            
            // Insert after view button
            const viewButton = actionsCell.querySelector('a');
            viewButton.after(stopButton);
        }
    }
});
</script>
@endsection 