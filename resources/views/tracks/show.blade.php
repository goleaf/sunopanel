@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">{{ $track->title }}</h1>
        <a href="{{ route('tracks.index') }}" class="btn btn-outline">Back to Songs</a>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card bg-base-200 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Track Info</h2>
                
                <div class="mt-4 space-y-4">
                    <div>
                        <span class="font-semibold">Status:</span>
                        @if ($track->status === 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif ($track->status === 'processing')
                            <span class="badge badge-warning">Processing</span>
                        @elseif ($track->status === 'failed')
                            <span class="badge badge-error">Failed</span>
                        @else
                            <span class="badge badge-info">Pending</span>
                        @endif
                    </div>
                    
                    <div>
                        <span class="font-semibold">Progress:</span>
                        <div class="mt-2">
                            @if ($track->status === 'processing')
                                <progress class="progress progress-primary w-full" value="{{ $track->progress }}" max="100"></progress>
                            @elseif ($track->status === 'completed')
                                <progress class="progress progress-success w-full" value="100" max="100"></progress>
                            @elseif ($track->status === 'failed')
                                <div class="tooltip" data-tip="{{ $track->error_message }}">
                                    <progress class="progress progress-error w-full" value="100" max="100"></progress>
                                </div>
                            @else
                                <progress class="progress w-full" value="0" max="100"></progress>
                            @endif
                        </div>
                    </div>
                    
                    @if ($track->error_message)
                        <div class="alert alert-error">
                            <span>{{ $track->error_message }}</span>
                        </div>
                    @endif
                    
                    <div>
                        <span class="font-semibold">Genres:</span>
                        <div class="mt-2">
                            @foreach ($track->genres as $genre)
                                <a href="{{ route('genres.show', $genre) }}" class="badge badge-primary mr-1">
                                    {{ $genre->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    
                    <div>
                        <span class="font-semibold">Created:</span>
                        <div>{{ $track->created_at->format('F j, Y, g:i a') }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card bg-base-200 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Media</h2>
                
                @if ($track->status === 'completed' && $track->mp4_path)
                    <div class="mt-4">
                        <video controls class="w-full h-auto rounded-lg">
                            <source src="{{ asset('storage/' . $track->mp4_path) }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                @elseif ($track->status === 'completed' && $track->mp3_path)
                    <div class="mt-4">
                        <audio controls class="w-full">
                            <source src="{{ asset('storage/' . $track->mp3_path) }}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                        
                        @if ($track->image_path)
                            <div class="mt-4">
                                <img src="{{ asset('storage/' . $track->image_path) }}" alt="{{ $track->title }}" class="w-full h-auto rounded-lg">
                            </div>
                        @endif
                    </div>
                @elseif ($track->status === 'processing' || $track->status === 'pending')
                    <div class="alert mt-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info flex-shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Media is still being processed. Please check back later.</span>
                    </div>
                @else
                    <div class="alert alert-error mt-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Failed to process media. {{ $track->error_message }}</span>
                    </div>
                @endif
                
                <div class="mt-6">
                    <h3 class="font-bold">Source Files</h3>
                    <div class="mt-2 space-y-2">
                        <div>
                            <span class="font-semibold">MP3 URL:</span>
                            <div class="truncate">
                                <a href="{{ $track->mp3_url }}" target="_blank" class="link link-primary">{{ $track->mp3_url }}</a>
                            </div>
                        </div>
                        
                        <div>
                            <span class="font-semibold">Image URL:</span>
                            <div class="truncate">
                                <a href="{{ $track->image_url }}" target="_blank" class="link link-primary">{{ $track->image_url }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-6 text-center">
        <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-error">Delete Track</button>
        </form>
    </div>
</div>

@if ($track->status === 'processing' || $track->status === 'pending')
    @push('scripts')
    <script>
        // Auto-refresh for processing tracks
        document.addEventListener('DOMContentLoaded', function() {
            setInterval(function() {
                fetch('{{ route('tracks.status', $track) }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status !== '{{ $track->status }}' || data.progress !== {{ $track->progress }}) {
                            window.location.reload();
                        }
                    });
            }, 3000); // Check every 3 seconds
        });
    </script>
    @endpush
@endif
@endsection 