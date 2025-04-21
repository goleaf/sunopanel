@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">YouTube Uploads</h1>
        
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
        
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title mb-4">Tracks Uploaded to YouTube</h2>
                
                @if ($tracks->isEmpty())
                    <div class="alert alert-info mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>No tracks have been uploaded to YouTube yet.</span>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                                <tr>
                                    <th>Track</th>
                                    <th>Uploaded</th>
                                    <th>YouTube</th>
                                    <th>Playlist</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tracks as $track)
                                    <tr>
                                        <td>
                                            <div class="flex items-center space-x-3">
                                                <div class="avatar">
                                                    <div class="mask mask-squircle w-12 h-12">
                                                        <img src="{{ $track->image_storage_url ?? asset('images/placeholder.png') }}" alt="{{ $track->title }}">
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="font-bold">{{ $track->title }}</div>
                                                    <div class="text-sm opacity-70">{{ $track->genres_list }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span title="{{ $track->youtube_uploaded_at }}">
                                                {{ $track->youtube_uploaded_at->diffForHumans() }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex space-x-2">
                                                <a href="{{ $track->youtube_url }}" target="_blank" class="btn btn-sm btn-primary" title="View on YouTube">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
                                                        <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('tracks.show', $track) }}" class="btn btn-sm" title="View Track Details">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($track->youtube_playlist_id)
                                                <a href="{{ $track->youtube_playlist_url }}" target="_blank" class="link link-primary">
                                                    View Playlist
                                                </a>
                                            @else
                                                <span class="text-sm opacity-70">No playlist</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6">
                        {{ $tracks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="mt-6 flex space-x-4">
        <a href="{{ route('youtube.status') }}" class="btn btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to YouTube Status
        </a>
        <a href="{{ route('youtube.upload.form') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            Upload New Video
        </a>
    </div>
</div>
@endsection 