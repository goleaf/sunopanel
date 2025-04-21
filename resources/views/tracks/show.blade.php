@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">{{ $track->title }}</h1>
        <div class="flex space-x-2">
            <a href="{{ route('tracks.index') }}" class="btn btn-outline">Back to Songs</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title">Track Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <h3 class="font-semibold mb-2">Status</h3>
                            <div id="track-status">
                                @if($track->status === 'completed')
                                <span class="badge badge-success">Completed</span>
                                @elseif($track->status === 'processing')
                                <span class="badge badge-warning">Processing</span>
                                @elseif($track->status === 'failed')
                                <span class="badge badge-error">Failed</span>
                                @else
                                <span class="badge badge-info">Pending</span>
                                @endif
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold mb-2">Progress</h3>
                            <div id="track-progress">
                                @if($track->status === 'processing')
                                <progress class="progress progress-primary w-full" value="{{ $track->progress }}" max="100"></progress>
                                <span class="text-xs text-right">{{ $track->progress }}%</span>
                                @elseif($track->status === 'completed')
                                <progress class="progress progress-success w-full" value="100" max="100"></progress>
                                <span class="text-xs text-right">100%</span>
                                @elseif($track->status === 'failed')
                                <progress class="progress progress-error w-full" value="100" max="100"></progress>
                                <span class="text-xs text-right">Error</span>
                                @else
                                <progress class="progress w-full" value="0" max="100"></progress>
                                <span class="text-xs text-right">0%</span>
                                @endif
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold mb-2">Genres</h3>
                            <div class="flex flex-wrap gap-1">
                                @forelse($track->genres as $genre)
                                <a href="{{ route('genres.show', $genre) }}" class="badge badge-primary">
                                    {{ $genre->name }}
                                </a>
                                @empty
                                <span class="text-gray-500">No genres specified</span>
                                @endforelse
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold mb-2">Created</h3>
                            <p>{{ $track->created_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                    </div>
                    
                    @if($track->error_message)
                    <div class="alert alert-error mt-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div>
                            <h3 class="font-bold">Error</h3>
                            <p class="text-sm">{{ $track->error_message }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            @if($track->status === 'completed' && $track->mp4_path)
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title">MP4 Video</h2>
                    <div class="mt-4">
                        <video id="track-video" controls class="w-full rounded-lg">
                            <source src="{{ $track->mp4_storage_url }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        <div>
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title">Source Files</h2>
                    <div class="mt-4">
                        <h3 class="font-semibold mb-2">MP3 Audio</h3>
                        @if($track->mp3_path)
                        <audio controls class="w-full">
                            <source src="{{ $track->mp3_storage_url }}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                        <div class="mt-2">
                            <a href="{{ $track->mp3_storage_url }}" download class="btn btn-sm btn-outline w-full">
                                Download MP3
                            </a>
                        </div>
                        @else
                        <p class="text-gray-500">Not yet downloaded</p>
                        @endif
                    </div>
                    
                    <div class="mt-4">
                        <h3 class="font-semibold mb-2">Cover Image</h3>
                        @if($track->image_path)
                        <img src="{{ $track->image_storage_url }}" alt="Cover image" class="rounded-lg w-full">
                        <div class="mt-2">
                            <a href="{{ $track->image_storage_url }}" download class="btn btn-sm btn-outline w-full">
                                Download Image
                            </a>
                        </div>
                        @else
                        <p class="text-gray-500">Not yet downloaded</p>
                        @endif
                    </div>
                    
                    @if($track->status === 'completed' && $track->mp4_path)
                    <div class="mt-4">
                        <h3 class="font-semibold mb-2">MP4 Video</h3>
                        <a href="{{ $track->mp4_storage_url }}" download class="btn btn-sm btn-primary w-full">
                            Download MP4
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Actions</h2>
                    <div class="mt-4 space-y-4">
                        <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-error w-full">Delete Track</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($track->status === 'processing' || $track->status === 'pending')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const trackId = {{ $track->id }};
    const statusEl = document.getElementById('track-status');
    const progressEl = document.getElementById('track-progress');
    
    function updateTrackStatus() {
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
                statusEl.innerHTML = statusHTML;
                
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
                    // Reload page to show completed files
                    window.location.reload();
                } else if (data.status === 'failed') {
                    progressHTML = `
                        <progress class="progress progress-error w-full" value="100" max="100"></progress>
                        <span class="text-xs text-right">Error</span>
                    `;
                    // Reload page to show error message
                    window.location.reload();
                } else {
                    progressHTML = `
                        <progress class="progress w-full" value="0" max="100"></progress>
                        <span class="text-xs text-right">0%</span>
                    `;
                }
                progressEl.innerHTML = progressHTML;
            })
            .catch(error => console.error('Error updating track status:', error));
    }
    
    // Initially update status, then every 3 seconds
    updateTrackStatus();
    setInterval(updateTrackStatus, 3000);
});
</script>
@endif
@endsection 