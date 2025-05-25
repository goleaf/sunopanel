@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">Upload Successful!</h1>
        
        <div class="alert alert-success mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>Your video "{{ $videoTitle }}" has been successfully uploaded to YouTube!</span>
        </div>
        
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title mb-4">Video Details</h2>
                
                <div class="mb-4">
                    <p><strong>Title:</strong> {{ $videoTitle }}</p>
                    <p class="mt-2"><strong>YouTube Video ID:</strong> {{ $videoId }}</p>
                    <p class="mt-2"><strong>YouTube Link:</strong> 
                        <a href="https://www.youtube.com/watch?v={{ $videoId }}" target="_blank" class="link link-primary">
                            https://www.youtube.com/watch?v={{ $videoId }}
                        </a>
                    </p>
                </div>
                
                <div class="mt-6 flex flex-wrap gap-4">
                    <a href="https://www.youtube.com/watch?v={{ $videoId }}" target="_blank" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        View on YouTube
                    </a>
                    
                    <a href="{{ route('videos.upload') }}" class="btn btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        Upload Another Video
                    </a>
                </div>
            </div>
        </div>
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
@endsection 