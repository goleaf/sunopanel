@extends('layouts.app')

@section('head')
    @vite(['resources/js/tracks.js'])
@endsection

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Page header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                </svg>
                Music Tracks
                @if($youtubeVisibilityFilter !== 'all')
                    <span class="badge badge-sm ml-2 {{ $youtubeVisibilityFilter === 'uploaded' ? 'badge-success' : 'badge-warning' }}">
                        {{ $youtubeVisibilityFilter === 'uploaded' ? 'Uploaded Only' : 'Not Uploaded Only' }}
                    </span>
                @endif
            </h1>
            <p class="text-sm text-base-content/70 mt-1">
                Manage and monitor your AI-generated music tracks
                @if($youtubeVisibilityFilter !== 'all')
                    <span class="text-xs">
                        • Global filter active: showing {{ $youtubeVisibilityFilter === 'uploaded' ? 'uploaded' : 'not uploaded' }} tracks only
                        <a href="{{ route('settings.index') }}" class="link link-primary">Change in Settings</a>
                    </span>
                @endif
            </p>
        </div>
        <div class="flex space-x-2 mt-4 md:mt-0">
            <a href="{{ route('home.index') }}" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add New Track
            </a>
            <button id="refresh-now" class="btn btn-outline btn-sm" title="Refresh Now">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Messages -->


    <!-- Stats cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <!-- Total -->
        <div class="card bg-base-100 shadow hover:shadow-md transition-shadow duration-300">
            <div class="card-body p-4">
                <div class="stat p-0">
                    <div class="stat-title text-xs">Total</div>
                    <div class="stat-value text-base-content text-2xl" data-count="total">{{ $totalTracks }}</div>
                </div>
            </div>
        </div>
        
        <!-- Processing -->
        <div class="card bg-base-100 shadow hover:shadow-md transition-shadow duration-300">
            <div class="card-body p-4">
                <div class="stat p-0">
                    <div class="stat-title text-xs">Processing</div>
                    <div class="stat-value text-warning text-2xl" data-count="processing">{{ $processingTracks }}</div>
                </div>
            </div>
        </div>
        
        <!-- Pending -->
        <div class="card bg-base-100 shadow hover:shadow-md transition-shadow duration-300">
            <div class="card-body p-4">
                <div class="stat p-0">
                    <div class="stat-title text-xs">Pending</div>
                    <div class="stat-value text-info text-2xl" data-count="pending">{{ $pendingTracks }}</div>
                </div>
            </div>
        </div>
        
        <!-- Completed -->
        <div class="card bg-base-100 shadow hover:shadow-md transition-shadow duration-300">
            <div class="card-body p-4">
                <div class="stat p-0">
                    <div class="stat-title text-xs">Completed</div>
                    <div class="stat-value text-success text-2xl" data-count="completed">{{ $completedTracks }}</div>
                </div>
            </div>
        </div>
        
        <!-- Failed -->
        <div class="card bg-base-100 shadow hover:shadow-md transition-shadow duration-300">
            <div class="card-body p-4">
                <div class="stat p-0">
                    <div class="stat-title text-xs">Failed</div>
                    <div class="stat-value text-error text-2xl" data-count="failed">{{ $failedTracks }}</div>
                </div>
            </div>
        </div>
        
        <!-- Stopped -->
        <div class="card bg-base-100 shadow hover:shadow-md transition-shadow duration-300">
            <div class="card-body p-4">
                <div class="stat p-0">
                    <div class="stat-title text-xs">Stopped</div>
                    <div class="stat-value text-warning text-2xl" data-count="stopped">{{ $stoppedTracks }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and filters -->
    <div class="card bg-base-100 shadow-md mb-6">
        <div class="card-body p-4">
            <div class="flex flex-wrap gap-4">
                <form action="{{ route('tracks.index') }}" method="GET" class="flex-1 flex gap-2 flex-wrap items-center">
                    <div class="input-group flex-1 max-w-md">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tracks..." class="input input-bordered w-full">
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
                    
                    <div class="flex-1 flex flex-wrap gap-1">
                        <button type="submit" name="status" value="" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline' }}">All</button>
                        <button type="submit" name="status" value="processing" class="btn btn-sm {{ request('status') == 'processing' ? 'btn-primary' : 'btn-outline' }}">Processing</button>
                        <button type="submit" name="status" value="pending" class="btn btn-sm {{ request('status') == 'pending' ? 'btn-primary' : 'btn-outline' }}">Pending</button>
                        <button type="submit" name="status" value="completed" class="btn btn-sm {{ request('status') == 'completed' ? 'btn-primary' : 'btn-outline' }}">Completed</button>
                        <button type="submit" name="status" value="failed" class="btn btn-sm {{ request('status') == 'failed' ? 'btn-primary' : 'btn-outline' }}">Failed</button>
                        <button type="submit" name="status" value="stopped" class="btn btn-sm {{ request('status') == 'stopped' ? 'btn-primary' : 'btn-outline' }}">Stopped</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Action Buttons Bar -->
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <div class="flex flex-wrap gap-2">
            <button id="start-all-tracks" class="btn btn-success btn-sm action-btn">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                </svg>
                Start All
            </button>
            <button id="stop-all-tracks" class="btn btn-error btn-sm action-btn">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18h12a1 1 0 001-1V7a1 1 0 00-1-1H6a1 1 0 00-1 1v10a1 1 0 001 1z" />
                </svg>
                Stop All
            </button>
            @if($failedTracks > 0)
            <button id="retry-all-tracks" class="btn btn-warning btn-sm action-btn">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Retry Failed
            </button>
            @endif
        </div>
        
        <div class="flex flex-wrap gap-3 items-center">
            <!-- Removed Show Completed and Auto-Refresh toggles -->
        </div>
    </div>

    <!-- Tracks table -->
    <div class="overflow-x-auto bg-base-100 rounded-lg shadow">
        <table class="table table-zebra w-full">
            <thead>
                <tr>
                    <th>Track</th>
                    <th>Genres</th>
                    <th>Status</th>
                    <th>Progress</th>
                    @if($showYoutubeColumn)
                    <th class="w-24">YouTube</th>
                    @endif
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tracks as $track)
                <tr data-track-id="{{ $track->id }}" class="track-row status-{{ $track->status }}">
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
                            <span class="text-xs progress-percentage">{{ $track->progress }}%</span>
                        </div>
                        @elseif($track->status === 'completed')
                        <div class="flex items-center">
                            <progress class="progress progress-xs progress-success flex-grow mr-1" value="100" max="100"></progress>
                            <span class="text-xs progress-percentage">100%</span>
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
                            <span class="text-xs progress-percentage">0%</span>
                        </div>
                        @endif
                    </td>
                    @if($showYoutubeColumn)
                    <td class="w-24 track-youtube">
                        @if($track->youtube_uploaded)
                            <div class="flex items-center justify-center">
                                <a href="{{ $track->youtube_video_url }}" target="_blank" class="tooltip" data-tip="View on YouTube">
                                    <span class="badge badge-sm {{ $track->youtube_enabled ? 'badge-success' : 'badge-warning' }}">
                                        {{ $track->youtube_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </a>
                                <button type="button" class="ml-2 btn btn-xs btn-circle {{ $track->youtube_enabled ? 'btn-success' : 'btn-outline btn-success' }} tooltip toggle-youtube-status" data-tip="{{ $track->youtube_enabled ? 'Disable YouTube' : 'Enable YouTube' }}" data-track-id="{{ $track->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $track->youtube_enabled ? 'M5 13l4 4L19 7' : 'M12 4v16m8-8H4' }}" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <button type="button" class="btn btn-xs btn-outline btn-primary toggle-youtube-status tooltip" data-tip="Mark as uploaded to YouTube" data-track-id="{{ $track->id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                YouTube
                            </button>
                        @endif
                    </td>
                    @endif
                    <td class="text-right">
                        <div class="flex space-x-1 justify-end">
                            <a href="{{ route('tracks.show', $track) }}" class="btn btn-sm btn-circle btn-ghost" title="View Details">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            
                            @if(in_array($track->status, ['failed', 'stopped']))
                            <button type="button" class="btn btn-sm btn-circle btn-success start-track action-btn" data-track-id="{{ $track->id }}" title="Start Processing">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                </svg>
                            </button>
                            @endif
                            
                            @if(in_array($track->status, ['processing', 'pending']))
                            <button type="button" class="btn btn-sm btn-circle btn-error stop-track action-btn" data-track-id="{{ $track->id }}" title="Stop Processing">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18h12a1 1 0 001-1V7a1 1 0 00-1-1H6a1 1 0 00-1 1v10a1 1 0 001 1z" />
                                </svg>
                            </button>
                            @endif
                            
                            @if($track->status === 'failed')
                            <button type="button" class="btn btn-sm btn-circle btn-warning retry-track action-btn" data-track-id="{{ $track->id }}" title="Retry Processing">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                            @endif
                            
                            @if($track->status === 'completed')
                            <button type="button" class="btn btn-sm btn-circle btn-warning redownload-track action-btn" data-track-id="{{ $track->id }}" title="Redownload and Process Again">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </button>
                            @endif
                            
                            <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-circle btn-error action-btn" title="Delete Track">
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

<!-- CSRF Token for API requests (used by JavaScript) -->
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@push('scripts')
@vite(['resources/js/youtube-toggle.js'])
@endpush 