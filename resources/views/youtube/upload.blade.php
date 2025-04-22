@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">Upload to YouTube</h1>
        
        @if (session('success'))
            <div class="alert alert-success mb-4">
                {{ session('success') }}
            </div>
        @endif
        
        @if (session('error'))
            <div class="alert alert-error mb-4">
                {{ session('error') }}
            </div>
        @endif
        
        @if (!$isAuthenticated)
            <div class="alert alert-warning mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span>You are not authenticated with YouTube. <a href="{{ route('youtube.auth.redirect') }}" class="link link-primary">Click here to authenticate</a>.</span>
            </div>
        @else
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">Upload Track to YouTube</h2>
                    
                    @if ($tracks->isEmpty())
                        <div class="alert alert-warning mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <span>No completed tracks found. Please wait for tracks to complete processing before uploading to YouTube.</span>
                        </div>
                    @else
                        <form action="{{ route('youtube.upload') }}" method="POST" class="space-y-6">
                            @csrf
                            
                            <div>
                                <label for="track_id" class="block text-sm font-medium mb-2">Select Track:</label>
                                <select id="track_id" name="track_id" class="select select-bordered w-full" required>
                                    <option value="">Select a track...</option>
                                    @foreach ($tracks as $track)
                                        <option value="{{ $track->id }}" @if($track->is_uploaded_to_youtube) disabled @endif>
                                            {{ $track->title }} 
                                            @if($track->is_uploaded_to_youtube) [Already uploaded] @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mt-4">
                                <label for="title" class="block text-sm font-medium mb-2">YouTube Title:</label>
                                <input type="text" id="title" name="title" class="input input-bordered w-full" required maxlength="100">
                                <p class="text-xs text-base-content/70 mt-1">Maximum 100 characters</p>
                            </div>
                            
                            <div class="mt-4">
                                <label for="description" class="block text-sm font-medium mb-2">YouTube Description:</label>
                                <textarea id="description" name="description" rows="4" class="textarea textarea-bordered w-full" maxlength="5000"></textarea>
                                <p class="text-xs text-base-content/70 mt-1">Maximum 5000 characters</p>
                            </div>
                            
                            <div class="mt-4">
                                <label for="privacy_status" class="block text-sm font-medium mb-2">Privacy:</label>
                                <select id="privacy_status" name="privacy_status" class="select select-bordered w-full" required>
                                    <option value="unlisted">Unlisted (anyone with the link can view)</option>
                                    <option value="public">Public (visible to everyone)</option>
                                    <option value="private">Private (only you can view)</option>
                                </select>
                            </div>
                            
                            <div class="mt-4">
                                <label class="cursor-pointer label">
                                    <span class="label-text">Not made for kids</span> 
                                    <input type="checkbox" name="not_for_kids" value="1" checked class="checkbox checkbox-primary" />
                                </label>
                                <p class="text-xs text-base-content/70 mt-1">Mark content as "Not Made for Kids" to avoid YouTube Kids restrictions</p>
                            </div>
                            
                            <div class="mt-4">
                                <label class="cursor-pointer label flex justify-between">
                                    <span class="label-text">Upload as regular video</span> 
                                    <input type="checkbox" name="is_regular_video" value="1" checked class="checkbox checkbox-primary" />
                                </label>
                                <p class="text-xs text-base-content/70 mt-1">Upload to your regular YouTube channel</p>
                            </div>
                            
                            <div class="mt-4">
                                <label class="cursor-pointer label flex justify-between">
                                    <span class="label-text">Upload as YouTube Short</span> 
                                    <input type="checkbox" name="is_short" value="1" checked class="checkbox checkbox-primary" />
                                </label>
                                <p class="text-xs text-base-content/70 mt-1">Upload as a YouTube Short for better visibility in Shorts feed</p>
                            </div>
                            
                            <div class="mt-4">
                                <label class="cursor-pointer label">
                                    <span class="label-text">Add to playlist based on genre</span> 
                                    <input type="checkbox" name="add_to_playlist" value="1" checked class="checkbox checkbox-primary" />
                                </label>
                                <p class="text-xs text-base-content/70 mt-1">If checked, the video will be added to a playlist named after the track's first genre</p>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    Upload to YouTube
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
    
    <div class="mt-6">
        <a href="{{ route('youtube.status') }}" class="btn btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to YouTube Status
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const trackSelect = document.getElementById('track_id');
        const titleInput = document.getElementById('title');
        
        if (trackSelect && titleInput) {
            trackSelect.addEventListener('change', function() {
                const trackId = this.value;
                if (trackId) {
                    const trackTitle = this.options[this.selectedIndex].text.replace(' [Already uploaded]', '');
                    titleInput.value = trackTitle;
                } else {
                    titleInput.value = '';
                }
            });
        }
    });
</script>
@endsection 