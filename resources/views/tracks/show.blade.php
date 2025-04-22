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
                            <div id="track-status" data-status="{{ $track->status }}" class="flex items-center">
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
                                @elseif($track->status === 'stopped')
                                <div class="badge badge-lg badge-warning gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18h12a1 1 0 001-1V7a1 1 0 00-1-1H6a1 1 0 00-1 1v10a1 1 0 001 1z" />
                                    </svg>
                                    Stopped
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
                            <div id="track-progress" data-progress="{{ $track->progress }}">
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
                                @elseif($track->status === 'stopped')
                                <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                                    <div class="bg-warning h-4 rounded-full" style="width: {{ $track->progress }}%"></div>
                                </div>
                                <span class="text-sm mt-1 inline-block">Processing stopped at {{ $track->progress }}%</span>
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
                            
                            <!-- Start/Stop/Retry buttons -->
                            @if(in_array($track->status, ['failed', 'stopped']))
                            <button type="button" id="start-processing" class="btn btn-success btn-sm gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                </svg>
                                Start Processing
                            </button>
                            @endif
                            
                            @if(in_array($track->status, ['processing', 'pending']))
                            <button type="button" id="stop-processing" class="btn btn-error btn-sm gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18h12a1 1 0 001-1V7a1 1 0 00-1-1H6a1 1 0 00-1 1v10a1 1 0 001 1z" />
                                </svg>
                                Stop Processing
                            </button>
                            @endif
                            
                            @if($track->status === 'failed')
                            <button type="button" id="retry-processing" class="btn btn-warning btn-sm gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Retry Processing
                            </button>
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

    <!-- Add this YouTube section right after the track details section, before the actions section -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title">YouTube Integration</h2>
            
            @if ($track->is_uploaded_to_youtube)
                <div class="alert alert-success mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>This track has been uploaded to YouTube.</span>
                </div>
                
                <div class="mb-4">
                    <div class="aspect-video w-full rounded-lg overflow-hidden">
                        <iframe 
                            src="{{ $track->youtube_embed_url }}" 
                            title="{{ $track->title }}" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen
                            class="w-full h-full"
                        ></iframe>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $track->youtube_url }}" target="_blank" class="btn btn-primary btn-sm gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Watch on YouTube
                    </a>
                    
                    @if ($track->youtube_playlist_url)
                    <a href="{{ $track->youtube_playlist_url }}" target="_blank" class="btn btn-secondary btn-sm gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            View Playlist
                        </a>
                    @endif
                </div>
            @elseif ($track->status === 'completed' && $track->mp4_path)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <div class="aspect-video w-full rounded-lg overflow-hidden bg-base-200 flex items-center justify-center">
                            <video 
                                controls
                                class="w-full h-full"
                                poster="{{ $track->image_storage_url }}"
                            >
                                <source src="{{ $track->mp4_storage_url }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    </div>
                    
                    <div>
                        <form action="{{ route('tracks.upload-to-youtube', $track) }}" method="POST" class="space-y-4">
                            @csrf
                            
                            <div>
                                <label for="title" class="block text-sm font-medium mb-2">YouTube Title:</label>
                                <input type="text" id="title" name="title" class="input input-bordered w-full" 
                                        value="{{ old('title', $track->title) }}" required maxlength="100">
                                <p class="text-xs text-base-content/70 mt-1">Maximum 100 characters</p>
                                @error('title')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <div class="mt-4">
                                <label for="description" class="block text-sm font-medium mb-2">YouTube Description:</label>
                                <textarea id="description" name="description" rows="4" class="textarea textarea-bordered w-full" 
                                        maxlength="5000">{{ old('description', "Track: {$track->title}\nGenres: {$track->genres_string}") }}</textarea>
                                <p class="text-xs text-base-content/70 mt-1">Maximum 5000 characters</p>
                                @error('description')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <div class="mt-4">
                                <label for="privacy_status" class="block text-sm font-medium mb-2">Privacy:</label>
                                <select id="privacy_status" name="privacy_status" class="select select-bordered w-full" required>
                                    <option value="public" {{ old('privacy_status') == 'public' ? 'selected' : '' }}>
                                        Public (visible to everyone)
                                    </option>
                                    <option value="unlisted" {{ old('privacy_status') == 'unlisted' ? 'selected' : '' }}>
                                        Unlisted (anyone with the link can view)
                                    </option>
                                    <option value="private" {{ old('privacy_status') == 'private' ? 'selected' : '' }}>
                                        Private (only you can view)
                                    </option>
                                </select>
                                @error('privacy_status')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <div class="alert alert-info mt-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <div>
                                    <span>Track will be uploaded to YouTube and added to a playlist based on its genre ({{ $track->genres_list }}).</span>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" class="btn btn-primary w-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
                                        <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
                                </svg>
                                Upload to YouTube
                            </button>
                            </div>
                        </form>
                    </div>
                    </div>
                @else
                <div class="alert alert-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <span>
                        @if ($track->status !== 'completed')
                            This track needs to be completed before uploading to YouTube.
                        @else
                            This track needs to have an MP4 file before uploading to YouTube.
                        @endif
                    </span>
                    </div>
            @endif
        </div>
    </div>

    <!-- YouTube Status Controls -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                YouTube Upload Status
            </h2>

            <div class="mt-4 track-youtube" data-track-id="{{ $track->id }}">
                @if ($track->youtube_video_id)
                    <div class="alert alert-success mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>This track is marked as uploaded to YouTube. Video ID: {{ $track->youtube_video_id }}</span>
                    </div>
                    
                    <button type="button" class="btn btn-error btn-sm toggle-youtube-status">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Mark as Not Uploaded
                    </button>
                    
                    @if($track->youtube_url)
                    <a href="{{ $track->youtube_url }}" target="_blank" class="btn btn-primary btn-sm ml-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        View on YouTube
                    </a>
                    @endif
                @else
                    <div class="alert alert-warning mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <span>This track is not marked as uploaded to YouTube.</span>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="youtube_video_id" class="block text-sm font-medium mb-2">YouTube Video ID (Optional):</label>
                            <input type="text" id="youtube_video_id" name="youtube_video_id" class="input input-bordered w-full" 
                                    placeholder="e.g. dQw4w9WgXcQ">
                            <p class="text-xs text-base-content/70 mt-1">If left empty, a placeholder ID will be used</p>
                        </div>
                        
                        <div>
                            <label for="youtube_playlist_id" class="block text-sm font-medium mb-2">YouTube Playlist ID (Optional):</label>
                            <input type="text" id="youtube_playlist_id" name="youtube_playlist_id" class="input input-bordered w-full" 
                                    placeholder="e.g. PLFgquLnL59alGJcdc0BEZJb2p7IgkL0Oe">
                        </div>
                        
                        <button type="button" class="btn btn-primary btn-sm toggle-youtube-status">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Mark as Uploaded
                        </button>
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
    const trackId = {{ $track->id }};
    const statusEl = document.getElementById('track-status');
    const progressEl = document.getElementById('track-progress');
    let currentStatus = "{{ $track->status }}";
    
    // Function to show a toast notification
    window.showToast = function(message, type = 'info') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        
        // Set the message and type
        toastMessage.textContent = message;
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
    
    // Initialize track status updater if track is processing or pending
    if (['processing', 'pending'].includes(currentStatus)) {
        const statusUpdater = new TrackStatusAPI({
            interval: 3000
        });
        
        // Register track for monitoring and enable page reload when complete
        statusUpdater.watchTrack(trackId, {
            status: statusEl,
            progress: progressEl,
        });
        
        // Start the updater
        statusUpdater.start();
    }
    
    // Handle YouTube toggle button clicks
    const youtubeToggleButtons = document.querySelectorAll('.toggle-youtube-status');
    youtubeToggleButtons.forEach(button => {
        button.addEventListener('click', async () => {
            // Disable button and show loading state
            button.disabled = true;
            button.classList.add('loading');
            
            try {
                // Get input values if they exist
                let data = {};
                const videoIdInput = document.getElementById('youtube_video_id');
                const playlistIdInput = document.getElementById('youtube_playlist_id');
                
                if (videoIdInput && videoIdInput.value) {
                    data.youtube_video_id = videoIdInput.value;
                }
                
                if (playlistIdInput && playlistIdInput.value) {
                    data.youtube_playlist_id = playlistIdInput.value;
                }
                
                // Get the CSRF token from meta tag
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Send request to toggle YouTube status
                const response = await fetch(`/tracks/${trackId}/toggle-youtube-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: Object.keys(data).length ? JSON.stringify(data) : null
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                // Reload the page to show updated status
                window.location.reload();
                
            } catch (error) {
                console.error('Error toggling YouTube status:', error);
                showToast('Failed to update YouTube status: ' + error.message, 'error');
                
                // Reset button state
                button.disabled = false;
                button.classList.remove('loading');
            }
        });
    });
});
</script>
@endsection 