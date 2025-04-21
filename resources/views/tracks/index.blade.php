@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Title and Statistics Summary -->
    <div class="mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
            <h1 class="text-2xl font-bold">Tracks Manager</h1>
            <a href="{{ route('home.index') }}" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add New Tracks
            </a>
        </div>

        <!-- Status Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            @php
                $totalTracks = $tracks->total();
                $completedCount = \App\Models\Track::where('status', 'completed')->count();
                $pendingCount = \App\Models\Track::where('status', 'pending')->count();
                $processingCount = \App\Models\Track::where('status', 'processing')->count();
                $failedCount = \App\Models\Track::where('status', 'failed')->count();
            @endphp
            
            <div class="stat bg-base-100 shadow rounded-lg p-3">
                <div class="stat-title">Total</div>
                <div class="stat-value text-xl">{{ $totalTracks }}</div>
                <div class="stat-desc">Tracks</div>
            </div>
            
            <div class="stat bg-base-100 shadow rounded-lg p-3">
                <div class="stat-title">Completed</div>
                <div class="stat-value text-xl text-success">{{ $completedCount }}</div>
                <div class="stat-desc">Ready to use</div>
            </div>
            
            <div class="stat bg-base-100 shadow rounded-lg p-3">
                <div class="stat-title">Processing</div>
                <div class="stat-value text-xl text-warning">{{ $processingCount }}</div>
                <div class="stat-desc">In progress</div>
            </div>
            
            <div class="stat bg-base-100 shadow rounded-lg p-3">
                <div class="stat-title">Pending</div>
                <div class="stat-value text-xl text-info">{{ $pendingCount }}</div>
                <div class="stat-desc">Waiting to process</div>
            </div>
            
            <div class="stat bg-base-100 shadow rounded-lg p-3">
                <div class="stat-title">Failed</div>
                <div class="stat-value text-xl text-error">{{ $failedCount }}</div>
                <div class="stat-desc">Need attention</div>
                @if($failedCount > 0)
                <div class="mt-2">
                    <form action="{{ route('tracks.retry-all') }}" method="POST" id="retryAllForm">
                        @csrf
                        <button type="button" onclick="confirmRetryAll()" class="btn btn-error btn-xs w-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Retry All
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="card bg-base-100 shadow mb-6">
        <div class="card-body p-4">
            <form action="{{ route('tracks.index') }}" method="GET" class="w-full">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <!-- Search -->
                    <div class="relative col-span-1 md:col-span-2">
                        <div class="flex w-full">
                            <span class="inline-flex items-center px-3 bg-base-200 border border-r-0 border-base-300 rounded-l-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input type="text" name="search" placeholder="Search by title..." value="{{ request('search') }}" 
                                class="input input-bordered rounded-l-none w-full" />
                        </div>
                    </div>
                    
                    <!-- Status Dropdown -->
                    <div>
                        <select name="status" class="select select-bordered w-full">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    
                    <!-- Genre Dropdown -->
                    <div>
                        <select name="genre" class="select select-bordered w-full">
                            <option value="">All Genres</option>
                            @php
                                $genres = \App\Models\Genre::orderBy('name')->get();
                            @endphp
                            @foreach($genres as $genre)
                                <option value="{{ $genre->slug }}" {{ request('genre') === $genre->slug ? 'selected' : '' }}>
                                    {{ $genre->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                    
                    <a href="{{ route('tracks.index') }}" class="btn btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-6">
        {{ session('success') }}
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning mb-6">
        {{ session('warning') }}
    </div>
    @endif

    @if(session('info'))
    <div class="alert alert-info mb-6">
        {{ session('info') }}
    </div>
    @endif

    <!-- Tracks Table -->
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            @if(session('failed_tracks'))
            <div class="alert alert-error mb-6">
                <h2 class="font-bold">Failed Tracks</h2>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('failed_tracks') as $failed)
                            <tr>
                                <td>{{ $failed['track']->title }}</td>
                                <td class="text-error">{{ $failed['error'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($tracks->isEmpty())
            <div class="alert">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>No tracks found. <a href="{{ route('home.index') }}" class="link link-primary">Add some tracks</a></span>
            </div>
            @else
            <!-- Table View -->
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Track</th>
                            <th>Genres</th>
                            <th class="w-24">Status</th>
                            <th class="w-32">Progress</th>
                            <th class="w-24 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tracks as $track)
                        <tr data-track-id="{{ $track->id }}" class="hover @if($track->status === 'processing') bg-warning bg-opacity-10 @endif">
                            <td>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('tracks.show', $track) }}" class="block">
                                        <div class="avatar">
                                            <div class="mask mask-squircle w-8 h-8 bg-base-200 flex items-center justify-center overflow-hidden">
                                                @if($track->image_path)
                                                    <img src="{{ $track->image_storage_url }}" alt="{{ $track->title }}" class="w-full h-full object-cover" />
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                    <div>
                                        <a href="{{ route('tracks.show', $track) }}" class="font-medium hover:underline">
                                            {{ Str::limit($track->title, 30) }}
                                        </a>
                                        <div class="text-xs opacity-60">
                                            {{ $track->created_at->format('Y-m-d') }}
                                            @if($track->mp3_path)
                                            · <span class="tooltip" data-tip="Play audio">
                                                <button onclick="toggleAudio('audio-{{ $track->id }}')" class="text-primary hover:underline">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($track->mp3_path)
                                <audio id="audio-{{ $track->id }}" class="hidden w-full h-6 mt-2">
                                    <source src="{{ $track->mp3_storage_url }}" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @forelse($track->genres as $genre)
                                    <a href="{{ route('genres.show', $genre) }}" class="badge badge-sm">
                                        {{ $genre->name }}
                                    </a>
                                    @empty
                                    <span class="text-xs text-gray-500">None</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="track-status">
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
                            <td>
                                <div class="track-progress">
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
                                </div>
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
                                    <form action="{{ route('tracks.retry', $track) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-circle btn-warning">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </form>
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
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $tracks->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to confirm retry all
    window.confirmRetryAll = function() {
        if (confirm('Are you sure you want to retry all failed tracks?')) {
            document.getElementById('retryAllForm').submit();
        }
    };
    
    const trackRows = document.querySelectorAll('[data-track-id]');
    
    // Function to toggle audio playback
    window.toggleAudio = function(audioId) {
        const audio = document.getElementById(audioId);
        
        // Hide all other audio players
        document.querySelectorAll('audio').forEach(player => {
            if (player.id !== audioId) {
                player.pause();
                player.classList.add('hidden');
            }
        });
        
        // Toggle current audio player
        if (audio.classList.contains('hidden')) {
            audio.classList.remove('hidden');
            audio.play();
        } else {
            audio.pause();
            audio.classList.add('hidden');
        }
    };
    
    // Update function for tracks that are in progress
    function updateTrackStatus() {
        trackRows.forEach(row => {
            const trackId = row.getAttribute('data-track-id');
            const statusCell = row.querySelector('.track-status');
            const progressCell = row.querySelector('.track-progress');
            
            if (statusCell.textContent.trim().includes('Processing') || 
                statusCell.textContent.trim().includes('Pending')) {
                
                fetch(`/tracks/${trackId}/status`)
                    .then(response => response.json())
                    .then(data => {
                        // Update status
                        let statusHTML;
                        if (data.status === 'completed') {
                            statusHTML = '<span class="badge badge-sm badge-success">Completed</span>';
                            row.classList.remove('bg-warning', 'bg-opacity-10');
                        } else if (data.status === 'processing') {
                            statusHTML = '<span class="badge badge-sm badge-warning">Processing</span>';
                            row.classList.add('bg-warning', 'bg-opacity-10');
                        } else if (data.status === 'failed') {
                            statusHTML = '<span class="badge badge-sm badge-error">Failed</span>';
                            row.classList.remove('bg-warning', 'bg-opacity-10');
                        } else {
                            statusHTML = '<span class="badge badge-sm badge-info">Pending</span>';
                            row.classList.remove('bg-warning', 'bg-opacity-10');
                        }
                        statusCell.innerHTML = statusHTML;
                        
                        // Update progress
                        let progressHTML;
                        if (data.status === 'processing') {
                            progressHTML = `
                                <div class="flex items-center">
                                    <progress class="progress progress-xs progress-warning flex-grow mr-1" value="${data.progress}" max="100"></progress>
                                    <span class="text-xs">${data.progress}%</span>
                                </div>
                            `;
                        } else if (data.status === 'completed') {
                            progressHTML = `
                                <div class="flex items-center">
                                    <progress class="progress progress-xs progress-success flex-grow mr-1" value="100" max="100"></progress>
                                    <span class="text-xs">100%</span>
                                </div>
                            `;
                        } else if (data.status === 'failed') {
                            progressHTML = `
                                <div class="tooltip w-full" data-tip="${data.error_message}">
                                    <progress class="progress progress-xs progress-error w-full" value="100" max="100"></progress>
                                </div>
                            `;
                        } else {
                            progressHTML = `
                                <div class="flex items-center">
                                    <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                                    <span class="text-xs">0%</span>
                                </div>
                            `;
                        }
                        progressCell.innerHTML = progressHTML;
                        
                        // Refresh status counts if status changed
                        if (data.status !== statusCell.textContent.trim()) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        }
                    })
                    .catch(error => console.error('Error updating track status:', error));
            }
        });
    }
    
    // Initially update status, then every 3 seconds
    updateTrackStatus();
    setInterval(updateTrackStatus, 3000);
});
</script>
@endsection 