@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Back button -->
    <div class="mb-4">
        <a href="{{ route('tracks.index') }}" class="btn btn-sm btn-outline gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Songs
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-6">
        {{ session('success') }}
    </div>
    @endif

    <!-- Track Header -->
    <div class="card bg-base-100 shadow-xl mb-6 overflow-hidden">
        <div class="md:flex">
            <div class="md:w-1/3 bg-base-200 flex items-center justify-center p-4">
                <div class="rounded-lg overflow-hidden w-full max-w-xs mx-auto shadow-md">
                    @if($track->image_path)
                        <img src="{{ $track->image_storage_url }}" alt="{{ $track->title }}" class="w-full aspect-square object-cover">
                    @else
                        <div class="w-full aspect-square bg-base-300 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                            </svg>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="md:w-2/3 p-6">
                <h1 class="text-3xl font-bold mb-2">{{ $track->title }}</h1>
                
                <div class="flex flex-wrap gap-1 mb-4">
                    @forelse($track->genres as $genre)
                    <a href="{{ route('genres.show', $genre) }}" class="badge badge-primary">
                        {{ $genre->name }}
                    </a>
                    @empty
                    <span class="text-gray-500">No genres specified</span>
                    @endforelse
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold mb-2">Status</h3>
                            <div id="track-status" class="flex items-center">
                                @if($track->status === 'completed')
                                <div class="badge badge-lg badge-success gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Completed
                                </div>
                                @elseif($track->status === 'processing')
                                <div class="badge badge-lg badge-warning gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Processing
                                </div>
                                @elseif($track->status === 'failed')
                                <div class="badge badge-lg badge-error gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Failed
                                </div>
                                @else
                                <div class="badge badge-lg badge-info gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Pending
                                </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold mb-2">Progress</h3>
                            <div id="track-progress">
                                @if($track->status === 'processing')
                                <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                                    <div class="bg-primary h-4 rounded-full transition-all duration-300" style="width: {{ $track->progress }}%"></div>
                                </div>
                                <span class="text-sm mt-1 inline-block">{{ $track->progress }}% complete</span>
                                @elseif($track->status === 'completed')
                                <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                                    <div class="bg-success h-4 rounded-full" style="width: 100%"></div>
                                </div>
                                <span class="text-sm mt-1 inline-block">100% complete</span>
                                @elseif($track->status === 'failed')
                                <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                                    <div class="bg-error h-4 rounded-full" style="width: 100%"></div>
                                </div>
                                <span class="text-sm mt-1 inline-block">Processing failed</span>
                                @else
                                <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                                    <div class="bg-info h-4 rounded-full" style="width: 0%"></div>
                                </div>
                                <span class="text-sm mt-1 inline-block">Waiting to start...</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold mb-2">MP3 Audio</h3>
                            @if($track->mp3_path)
                            <audio controls class="w-full">
                                <source src="{{ $track->mp3_storage_url }}" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            @else
                            <p class="text-gray-500">MP3 file not yet downloaded</p>
                            @endif
                        </div>
                        
                        <div class="flex flex-wrap gap-2 mt-4">
                            @if($track->mp3_path)
                            <a href="{{ $track->mp3_storage_url }}" download class="btn btn-outline btn-sm gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                MP3
                            </a>
                            @endif
                            
                            @if($track->image_path)
                            <a href="{{ $track->image_storage_url }}" download class="btn btn-outline btn-sm gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Image
                            </a>
                            @endif
                            
                            @if($track->status === 'completed' && $track->mp4_path)
                            <a href="{{ $track->mp4_storage_url }}" download class="btn btn-primary btn-sm gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                MP4 Video
                            </a>
                            @endif
                            
                            @if($track->status === 'failed')
                            <form action="{{ route('tracks.retry', $track) }}" method="POST">
                                @csrf
                                <input type="hidden" name="redirect_back" value="1">
                                <button type="submit" class="btn btn-warning btn-sm gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Retry Processing
                                </button>
                            </form>
                            @endif
                            
                            <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-error btn-sm gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                @if($track->error_message)
                <div class="alert alert-error mt-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="font-bold">Processing Error</h3>
                        <div class="text-sm max-h-24 overflow-y-auto">{{ $track->error_message }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MP4 Video Section (if available) -->
    @if(($track->status === 'completed' || $track->status === 'processing') && $track->mp4_path)
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                MP4 Video
                @if($track->status === 'processing')
                <span class="badge badge-warning">Processing</span>
                @endif
            </h2>
            
            <div class="mt-4">
                <div class="max-w-[700px] mx-auto">
                    <video controls class="w-full rounded-lg max-h-[700px] object-contain">
                        <source src="{{ $track->mp4_storage_url }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                
                @if($track->status === 'processing')
                <div class="alert alert-info mt-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>This video is still being processed. The preview may be incomplete or lower quality than the final version.</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Additional Information Section -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Additional Information
            </h2>
            
            <div class="overflow-x-auto mt-4">
                <table class="table w-full">
                    <tbody>
                        <tr>
                            <td class="font-semibold">Added</td>
                            <td>{{ $track->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td class="font-semibold">Last Updated</td>
                            <td>{{ $track->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td class="font-semibold">MP3 URL</td>
                            <td class="break-all">
                                <a href="{{ $track->mp3_url }}" target="_blank" class="link link-primary">{{ $track->mp3_url }}</a>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">Image URL</td>
                            <td class="break-all">
                                <a href="{{ $track->image_url }}" target="_blank" class="link link-primary">{{ $track->image_url }}</a>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold">Genre Tags</td>
                            <td>{{ $track->genres_string }}</td>
                        </tr>
                    </tbody>
                </table>
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
                    statusHTML = `
                        <div class="badge badge-lg badge-success gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Completed
                        </div>`;
                } else if (data.status === 'processing') {
                    statusHTML = `
                        <div class="badge badge-lg badge-warning gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Processing
                        </div>`;
                } else if (data.status === 'failed') {
                    statusHTML = `
                        <div class="badge badge-lg badge-error gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Failed
                        </div>`;
                } else {
                    statusHTML = `
                        <div class="badge badge-lg badge-info gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Pending
                        </div>`;
                }
                statusEl.innerHTML = statusHTML;
                
                // Update progress
                let progressHTML;
                if (data.status === 'processing') {
                    progressHTML = `
                        <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                            <div class="bg-primary h-4 rounded-full transition-all duration-300" style="width: ${data.progress}%"></div>
                        </div>
                        <span class="text-sm mt-1 inline-block">${data.progress}% complete</span>
                    `;
                } else if (data.status === 'completed') {
                    progressHTML = `
                        <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                            <div class="bg-success h-4 rounded-full" style="width: 100%"></div>
                        </div>
                        <span class="text-sm mt-1 inline-block">100% complete</span>
                    `;
                    // Reload page after a short delay to show completed state
                    setTimeout(() => window.location.reload(), 1000);
                } else if (data.status === 'failed') {
                    progressHTML = `
                        <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                            <div class="bg-error h-4 rounded-full" style="width: 100%"></div>
                        </div>
                        <span class="text-sm mt-1 inline-block">Processing failed</span>
                    `;
                    // Reload page after a short delay to show error details
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    progressHTML = `
                        <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                            <div class="bg-info h-4 rounded-full" style="width: 0%"></div>
                        </div>
                        <span class="text-sm mt-1 inline-block">Waiting to start...</span>
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