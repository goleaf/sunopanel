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
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <select name="status" class="select select-bordered select-sm pl-9 bg-gray-50 pr-8">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    
                    <!-- Genre -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <select name="genre" class="select select-bordered select-sm pl-9 bg-gray-50 pr-8">
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
                    
                    <!-- View Toggle -->
                    <div class="relative">
                        <input type="hidden" name="view" value="{{ request('view', 'table') }}">
                        <div class="join">
                            <button type="button" onclick="setView('table')" class="join-item btn btn-sm {{ request('view', 'table') === 'table' ? 'btn-active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 18h18M3 6h18" />
                                </svg>
                            </button>
                            <button type="button" onclick="setView('grid')" class="join-item btn btn-sm {{ request('view') === 'grid' ? 'btn-active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                        </div>
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
                @if(request('view') === 'grid')
                <!-- Grid View -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($tracks as $track)
                    <div class="card bg-base-200 shadow-md hover:shadow-xl transition-shadow duration-300 relative" data-track-id="{{ $track->id }}">
                        <!-- Status Badge -->
                        <div class="absolute top-2 right-2 z-10 track-status">
                            @if($track->status === 'completed')
                            <div class="badge badge-success">Completed</div>
                            @elseif($track->status === 'processing')
                            <div class="badge badge-warning">Processing</div>
                            @elseif($track->status === 'failed')
                            <div class="badge badge-error">Failed</div>
                            @else
                            <div class="badge badge-info">Pending</div>
                            @endif
                        </div>
                        
                        <!-- Track Image -->
                        <figure class="h-40 bg-base-300 flex items-center justify-center">
                            @if($track->image_path)
                                <img src="{{ $track->image_storage_url }}" alt="{{ $track->title }}" class="w-full h-full object-cover" />
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-base-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                    </svg>
                                </div>
                            @endif
                        </figure>
                        
                        <div class="card-body p-4">
                            <!-- Track Title -->
                            <h2 class="card-title text-base">
                                <a href="{{ route('tracks.show', $track) }}" class="hover:underline truncate">
                                    {{ $track->title }}
                                </a>
                            </h2>
                            
                            <!-- Genres -->
                            <div class="flex flex-wrap gap-1 mb-2">
                                @foreach($track->genres as $genre)
                                <a href="{{ route('genres.show', $genre) }}" class="badge badge-sm">{{ $genre->name }}</a>
                                @endforeach
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="track-progress my-2">
                                @if($track->status === 'processing')
                                <progress class="progress progress-primary w-full" value="{{ $track->progress }}" max="100"></progress>
                                <span class="text-xs">{{ $track->progress }}%</span>
                                @elseif($track->status === 'completed')
                                <progress class="progress progress-success w-full" value="100" max="100"></progress>
                                <span class="text-xs">100%</span>
                                @elseif($track->status === 'failed')
                                <div class="tooltip" data-tip="{{ $track->error_message }}">
                                    <progress class="progress progress-error w-full" value="100" max="100"></progress>
                                </div>
                                @else
                                <progress class="progress w-full" value="0" max="100"></progress>
                                <span class="text-xs">0%</span>
                                @endif
                            </div>
                            
                            <!-- Actions -->
                            <div class="card-actions justify-end mt-2">
                                <a href="{{ route('tracks.show', $track) }}" class="btn btn-xs btn-outline">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                @if($track->status === 'failed')
                                <form action="{{ route('tracks.retry', $track) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-warning">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-error">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <!-- Table View -->
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="w-1/3">Track</th>
                                <th>Genres</th>
                                <th class="w-24">Status</th>
                                <th class="w-32">Progress</th>
                                <th class="w-36 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tracks as $track)
                            <tr data-track-id="{{ $track->id }}" class="hover">
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <div class="avatar">
                                            <div class="mask mask-squircle w-10 h-10 bg-base-200 flex items-center justify-center overflow-hidden">
                                                @if($track->image_path)
                                                    <img src="{{ $track->image_storage_url }}" alt="{{ $track->title }}" class="w-full h-full object-cover" />
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                        <div>
                                            <a href="{{ route('tracks.show', $track) }}" class="font-medium hover:underline">
                                                {{ $track->title }}
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($track->genres as $genre)
                                        <a href="{{ route('genres.show', $genre) }}" class="badge badge-sm">{{ $genre->name }}</a>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="track-status">
                                    @if($track->status === 'completed')
                                    <span class="badge badge-success">Completed</span>
                                    @elseif($track->status === 'processing')
                                    <span class="badge badge-warning">Processing</span>
                                    @elseif($track->status === 'failed')
                                    <span class="badge badge-error">Failed</span>
                                    @else
                                    <span class="badge badge-info">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="track-progress">
                                        @if($track->status === 'processing')
                                        <progress class="progress progress-primary w-full" value="{{ $track->progress }}" max="100"></progress>
                                        <span class="text-xs text-right">{{ $track->progress }}%</span>
                                        @elseif($track->status === 'completed')
                                        <progress class="progress progress-success w-full" value="100" max="100"></progress>
                                        <span class="text-xs text-right">100%</span>
                                        @elseif($track->status === 'failed')
                                        <div class="tooltip" data-tip="{{ $track->error_message }}">
                                            <progress class="progress progress-error w-full" value="100" max="100"></progress>
                                        </div>
                                        @else
                                        <progress class="progress w-full" value="0" max="100"></progress>
                                        <span class="text-xs text-right">0%</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="flex space-x-1 justify-end">
                                        <a href="{{ route('tracks.show', $track) }}" class="btn btn-sm btn-outline">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        @if($track->status === 'failed')
                                        <form action="{{ route('tracks.retry', $track) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </button>
                                        </form>
                                        @endif
                                        <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-error">
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
                @endif
            
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
            
            if (statusCell.textContent.trim().includes('Processing') || 
                statusCell.textContent.trim().includes('Pending')) {
                
                fetch(`/tracks/${trackId}/status`)
                    .then(response => response.json())
                    .then(data => {
                        // Update status
                        let statusHTML;
                        if (data.status === 'completed') {
                            statusHTML = '<span class="badge badge-success">Completed</span>';
                        } else if (data.status === 'processing') {
                            statusHTML = '<span class="badge badge-warning">Processing</span>';
                        } else if (data.status === 'failed') {
                            statusHTML = '<span class="badge badge-error">Failed</span>';
                        } else {
                            statusHTML = '<span class="badge badge-info">Pending</span>';
                        }
                        
                        // Update progress
                        let progressHTML;
                        if (data.status === 'processing') {
                            progressHTML = `
                                <progress class="progress progress-primary w-full" value="${data.progress}" max="100"></progress>
                                <span class="text-xs text-right">${data.progress}%</span>
                            `;
                        } else if (data.status === 'completed') {
                            progressHTML = `
                                <progress class="progress progress-success w-full" value="100" max="100"></progress>
                                <span class="text-xs text-right">100%</span>
                            `;
                        } else if (data.status === 'failed') {
                            progressHTML = `
                                <div class="tooltip" data-tip="${data.error_message}">
                                    <progress class="progress progress-error w-full" value="100" max="100"></progress>
                                </div>
                            `;
                        } else {
                            progressHTML = `
                                <progress class="progress w-full" value="0" max="100"></progress>
                                <span class="text-xs text-right">0%</span>
                            `;
                        }
                        
                        // Apply updates
                        statusCell.innerHTML = statusHTML;
                        progressCell.innerHTML = progressHTML;
                        
                        // If still in progress, continue updating
                        if (data.status === 'processing' || data.status === 'pending') {
                            setTimeout(() => {
                                updateTrackStatus();
                            }, 5000);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching track status:', error);
                    });
            }
        });
    }
    
    // Start updating track status if needed
    if (trackRows.length > 0) {
        updateTrackStatus();
    }
});

// View toggle functionality
function setView(viewType) {
    // Update the hidden input value
    document.querySelector('input[name="view"]').value = viewType;
    
    // Submit the form
    document.querySelector('form').submit();
}
</script>
@endif
@endsection 