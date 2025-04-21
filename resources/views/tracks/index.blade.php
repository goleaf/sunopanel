@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Section header with stats -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 gap-4">
        <div class="w-full md:w-auto">
            <div class="flex flex-wrap gap-2">
                <!-- search bar and filter buttons -->
                <div class="join">
                    <form action="{{ route('tracks.index') }}" method="GET" class="join-item">
                        <div class="input-group">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tracks..." class="input input-bordered w-full max-w-xs">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="join">
                    <a href="{{ route('tracks.index') }}" class="btn join-item {{ !request('status') ? 'btn-primary' : 'btn-outline' }}">All</a>
                    <a href="{{ route('tracks.index', ['status' => 'processing']) }}" class="btn join-item {{ request('status') == 'processing' ? 'btn-primary' : 'btn-outline' }}">Processing</a>
                    <a href="{{ route('tracks.index', ['status' => 'completed']) }}" class="btn join-item {{ request('status') == 'completed' ? 'btn-primary' : 'btn-outline' }}">Completed</a>
                    <a href="{{ route('tracks.index', ['status' => 'failed']) }}" class="btn join-item {{ request('status') == 'failed' ? 'btn-primary' : 'btn-outline' }}">Failed</a>
                </div>
                
                @if($tracks->where('status', 'failed')->count() > 0)
                <form action="{{ route('tracks.retry-all') }}" method="POST" onsubmit="return confirm('Retry all failed tracks?')">
                    @csrf
                    <button type="submit" class="btn btn-warning">Retry All Failed</button>
                </form>
                @endif
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
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tracks as $track)
                <tr data-track-id="{{ $track->id }}" class="{{ $track->status === 'processing' ? 'bg-warning bg-opacity-10' : '' }}">
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
                        @else
                        <div class="flex items-center">
                            <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                            <span class="text-xs">0%</span>
                        </div>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="flex space-x-1 justify-end">
                            <a href="{{ route('tracks.show', $track) }}" class="btn btn-xs btn-circle btn-ghost">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            @if($track->status === 'failed')
                            <button type="button" class="btn btn-xs btn-circle btn-warning retry-track" data-track-id="{{ $track->id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                            @endif
                            <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-circle btn-error">
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
                    <td colspan="5" class="text-center py-4">
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

@if($tracks->where('status', 'processing')->count() > 0 || $tracks->where('status', 'pending')->count() > 0)
<!-- CSRF Token for API requests -->
<meta name="csrf-token" content="{{ csrf_token() }}">

@vite(['resources/js/track-status.js'])
<script type="module">
import TrackStatusAPI from "{{ Vite::asset('resources/js/track-status.js') }}";

document.addEventListener('DOMContentLoaded', function() {
    // Initialize track status updater
    const statusUpdater = new TrackStatusAPI({
        interval: 3000
    });
    
    // Register all tracks that need monitoring
    const trackRows = document.querySelectorAll('[data-track-id]');
    trackRows.forEach(row => {
        const trackId = row.getAttribute('data-track-id');
        const statusCell = row.querySelector('.track-status');
        const progressCell = row.querySelector('.track-progress');
        
        if (statusCell.textContent.trim().includes('Processing') || 
            statusCell.textContent.trim().includes('Pending')) {
            
            statusUpdater.watchTrack(trackId, {
                status: statusCell,
                progress: progressCell
            });
        }
    });
    
    // Start the status updater
    statusUpdater.start();
    
    // Setup retry buttons
    document.querySelectorAll('.retry-track').forEach(button => {
        button.addEventListener('click', async function() {
            const trackId = this.getAttribute('data-track-id');
            
            try {
                // Show loading state
                this.classList.add('loading');
                
                // Call retry API
                const result = await TrackStatusAPI.retryTrack(trackId);
                
                if (result.success) {
                    // Update UI immediately
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
                    
                    // Add to status updater if not already watching
                    statusUpdater.watchTrack(trackId, {
                        status: statusCell,
                        progress: progressCell
                    });
                    
                    // Show toast if available
                    if (window.showToast) {
                        window.showToast('Track requeued for processing', 'success');
                    }
                }
            } catch (error) {
                console.error('Failed to retry track:', error);
                if (window.showToast) {
                    window.showToast('Failed to retry track: ' + error.message, 'error');
                }
            } finally {
                this.classList.remove('loading');
            }
        });
    });
});
</script>
@endif
@endsection 