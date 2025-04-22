@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">Upload Video to YouTube</h1>
        
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
        
        @if (!$hasCredentials)
            <div class="alert alert-warning mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span>YouTube credentials are not set. <a href="{{ route('youtube.status') }}" class="link link-primary">Click here to set up YouTube credentials</a>.</span>
            </div>
        @else
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">Upload Video</h2>
                    
                    <form action="{{ route('videos.upload.process') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        <div>
                            <label for="video" class="block text-sm font-medium mb-2">Video File:</label>
                            <input type="file" id="video" name="video" 
                                   class="file-input file-input-bordered w-full" 
                                   accept="video/mp4,video/x-m4v,video/*" required>
                            <p class="text-xs text-base-content/70 mt-1">Max file size: 500MB. Supported formats: MP4, MOV, AVI, WMV</p>
                            @error('video')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="mt-4">
                            <label for="title" class="block text-sm font-medium mb-2">YouTube Title:</label>
                            <input type="text" id="title" name="title" class="input input-bordered w-full" 
                                   value="{{ old('title') }}" required maxlength="100">
                            <p class="text-xs text-base-content/70 mt-1">Maximum 100 characters</p>
                            @error('title')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="mt-4">
                            <label for="description" class="block text-sm font-medium mb-2">YouTube Description:</label>
                            <textarea id="description" name="description" rows="4" class="textarea textarea-bordered w-full" 
                                      maxlength="5000">{{ old('description') }}</textarea>
                            <p class="text-xs text-base-content/70 mt-1">Maximum 5000 characters</p>
                            @error('description')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="mt-4">
                            <label for="tags" class="block text-sm font-medium mb-2">Tags (comma separated):</label>
                            <input type="text" id="tags" name="tags" class="input input-bordered w-full" 
                                   value="{{ old('tags') }}" placeholder="music, video, etc">
                            <p class="text-xs text-base-content/70 mt-1">Enter tags separated by commas</p>
                            @error('tags')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="mt-4">
                            <label for="privacy_status" class="block text-sm font-medium mb-2">Privacy:</label>
                            <select id="privacy_status" name="privacy_status" class="select select-bordered w-full" required>
                                <option value="unlisted" {{ old('privacy_status') == 'unlisted' ? 'selected' : '' }}>
                                    Unlisted (anyone with the link can view)
                                </option>
                                <option value="public" {{ old('privacy_status') == 'public' ? 'selected' : '' }}>
                                    Public (visible to everyone)
                                </option>
                                <option value="private" {{ old('privacy_status') == 'private' ? 'selected' : '' }}>
                                    Private (only you can view)
                                </option>
                            </select>
                            @error('privacy_status')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
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
                </div>
            </div>
        @endif
    </div>
    
    <div class="mt-6">
        <a href="{{ route('home.index') }}" class="btn btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Home
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const videoInput = document.getElementById('video');
        const titleInput = document.getElementById('title');
        
        if (videoInput && titleInput) {
            videoInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    // Set the title to the filename (without extension) if the title is empty
                    if (!titleInput.value) {
                        const fileName = this.files[0].name;
                        const fileNameWithoutExt = fileName.split('.').slice(0, -1).join('.');
                        titleInput.value = fileNameWithoutExt;
                    }
                }
            });
        }
    });
</script>
@endsection 