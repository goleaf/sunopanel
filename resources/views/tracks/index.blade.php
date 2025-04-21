@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Search and Filter Section -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body p-4">
            <form action="{{ route('tracks.index') }}" method="GET">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search -->
                    <div class="relative flex-1 min-w-[200px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" placeholder="Search by title..." value="{{ request('search') }}" 
                            class="input input-bordered input-sm pl-10 w-full bg-gray-50">
                    </div>
                    
                    <!-- Status -->
                    <div class="relative min-w-[150px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <select name="status" class="select select-bordered select-sm pl-9 bg-gray-50 pr-8 w-full">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    
                    <!-- Genre -->
                    <div class="relative min-w-[150px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <select name="genre" class="select select-bordered select-sm pl-9 bg-gray-50 pr-8 w-full">
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
                    
                    <!-- Buttons -->
                    <div class="flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filter
                        </button>
                        <a href="{{ route('tracks.index') }}" class="btn btn-sm btn-outline">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset
                        </a>
                    </div>
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

    @if(session('failed_tracks'))
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title text-error">Failed Tracks</h2>
            <div class="overflow-x-auto">
                <table class="table w-full">
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
    </div>
    @endif

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if($tracks->isEmpty())
            <div class="alert">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>No tracks found. <a href="{{ route('home.index') }}" class="link link-primary">Add some tracks</a></span>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <tbody>
                        @foreach($tracks as $track)
                        <tr data-track-id="{{ $track->id }}" class="hover @if($track->status === 'processing') bg-warning bg-opacity-10 @endif">
                            <td class="p-4">
                                <div class="flex items-start gap-6">
                                    <!-- Track Image -->
                                    <a href="{{ route('tracks.show', $track) }}" class="block shrink-0">
                                        <div class="aspect-square w-28 h-28 bg-base-200 rounded-lg overflow-hidden shadow hover:shadow-md transition-shadow">
                                            @if($track->image_path)
                                                <img src="{{ $track->image_storage_url }}" alt="{{ $track->title }}" class="w-full h-full object-cover" />
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                    </a>
                                    
                                    <!-- Track Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                            <a href="{{ route('tracks.show', $track) }}" class="font-medium hover:underline text-xl truncate">
                                                {{ $track->title }}
                                            </a>
                                            
                                            <!-- Status and Progress -->
                                            <div class="flex items-center gap-3">
                                                <div class="track-status">
                                                    @if($track->status === 'completed')
                                                    <span class="badge badge-success px-3 py-3">Completed</span>
                                                    @elseif($track->status === 'processing')
                                                    <span class="badge badge-warning px-3 py-3">Processing</span>
                                                    @elseif($track->status === 'failed')
                                                    <span class="badge badge-error px-3 py-3">Failed</span>
                                                    @else
                                                    <span class="badge badge-info px-3 py-3">Pending</span>
                                                    @endif
                                                </div>
                                                
                                                <div class="track-percentage text-sm font-medium w-14 text-right">
                                                    @if($track->status === 'processing')
                                                    {{ $track->progress }}%
                                                    @elseif($track->status === 'completed')
                                                    100%
                                                    @elseif($track->status === 'failed')
                                                    Error
                                                    @else
                                                    0%
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Added date -->
                                        <div class="text-sm opacity-60 mt-1">
                                            Added: {{ $track->created_at->format('Y-m-d H:i') }}
                                        </div>
                                        
                                        <!-- Genres -->
                                        <div class="mt-3">
                                            <div class="flex flex-wrap gap-1">
                                                @forelse($track->genres as $genre)
                                                <a href="{{ route('genres.show', $genre) }}" class="badge badge-primary px-3 py-3">
                                                    {{ $genre->name }}
                                                </a>
                                                @empty
                                                <span class="text-gray-500">No genres</span>
                                                @endforelse
                                            </div>
                                        </div>
                                        
                                        <!-- Progress Bar -->
                                        <div class="mt-3 track-progress">
                                            @if($track->status === 'processing')
                                            <progress class="progress progress-primary w-full h-3" value="{{ $track->progress }}" max="100"></progress>
                                            @elseif($track->status === 'completed')
                                            <progress class="progress progress-success w-full h-3" value="100" max="100"></progress>
                                            @elseif($track->status === 'failed')
                                            <div class="tooltip w-full" data-tip="{{ $track->error_message }}">
                                                <progress class="progress progress-error w-full h-3" value="100" max="100"></progress>
                                            </div>
                                            @else
                                            <progress class="progress w-full h-3" value="0" max="100"></progress>
                                            @endif
                                        </div>
                                        
                                        <!-- Audio player -->
                                        @if($track->mp3_path)
                                        <div class="mt-3">
                                            <audio controls class="w-full h-8 max-w-xs">
                                                <source src="{{ $track->mp3_storage_url }}" type="audio/mpeg">
                                                Your browser does not support the audio element.
                                            </audio>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex flex-col gap-2">
                                        <a href="{{ route('tracks.show', $track) }}" class="btn btn-sm btn-circle btn-outline">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        @if($track->status === 'failed')
                                        <form action="{{ route('tracks.retry', $track) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-circle btn-warning">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </button>
                                        </form>
                                        @endif
                                        <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-circle btn-error">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
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

@if(!$tracks->isEmpty())
<script>
document.addEventListener('DOMContentLoaded', function() {
    const trackRows = document.querySelectorAll('[data-track-id]');
    
    // Update function for tracks that are in progress
    function updateTrackStatus() {
        trackRows.forEach(row => {
            const trackId = row.getAttribute('data-track-id');
            const statusCell = row.querySelector('.track-status');
            const progressCell = row.querySelector('.track-progress');
            const percentageCell = row.querySelector('.track-percentage');
            
            if (statusCell.textContent.trim().includes('Processing') || 
                statusCell.textContent.trim().includes('Pending')) {
                
                fetch(`/tracks/${trackId}/status`)
                    .then(response => response.json())
                    .then(data => {
                        // Update status
                        let statusHTML;
                        if (data.status === 'completed') {
                            statusHTML = '<span class="badge badge-success px-3 py-3">Completed</span>';
                            row.classList.remove('bg-warning', 'bg-opacity-10');
                        } else if (data.status === 'processing') {
                            statusHTML = '<span class="badge badge-warning px-3 py-3">Processing</span>';
                            row.classList.add('bg-warning', 'bg-opacity-10');
                        } else if (data.status === 'failed') {
                            statusHTML = '<span class="badge badge-error px-3 py-3">Failed</span>';
                            row.classList.remove('bg-warning', 'bg-opacity-10');
                        } else {
                            statusHTML = '<span class="badge badge-info px-3 py-3">Pending</span>';
                            row.classList.remove('bg-warning', 'bg-opacity-10');
                        }
                        statusCell.innerHTML = statusHTML;
                        
                        // Update percentage
                        if (percentageCell) {
                            if (data.status === 'processing') {
                                percentageCell.textContent = `${data.progress}%`;
                            } else if (data.status === 'completed') {
                                percentageCell.textContent = '100%';
                            } else if (data.status === 'failed') {
                                percentageCell.textContent = 'Error';
                            } else {
                                percentageCell.textContent = '0%';
                            }
                        }
                        
                        // Update progress
                        let progressHTML;
                        if (data.status === 'processing') {
                            progressHTML = `
                                <progress class="progress progress-primary w-full h-3" value="${data.progress}" max="100"></progress>
                            `;
                        } else if (data.status === 'completed') {
                            progressHTML = `
                                <progress class="progress progress-success w-full h-3" value="100" max="100"></progress>
                            `;
                        } else if (data.status === 'failed') {
                            progressHTML = `
                                <div class="tooltip w-full" data-tip="${data.error_message}">
                                    <progress class="progress progress-error w-full h-3" value="100" max="100"></progress>
                                </div>
                            `;
                        } else {
                            progressHTML = `
                                <progress class="progress w-full h-3" value="0" max="100"></progress>
                            `;
                        }
                        progressCell.innerHTML = progressHTML;
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
@endif
@endsection 