@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">YouTube Video Statistics</h1>
        
        <div class="flex justify-between items-center mb-6">
            <div>
                <a href="{{ route('youtube.uploads') }}" class="btn btn-sm btn-outline gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    Back to Uploads
                </a>
            </div>
            
            <form action="{{ route('youtube.video.refresh-stats', $videoId) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh Stats
                </button>
            </form>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Track Information -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Track Information</h2>
                    
                    <div class="flex items-center space-x-4 mt-4">
                        <div class="avatar">
                            <div class="w-24 h-24 rounded-lg">
                                <img src="{{ $track->image_storage_url ?? asset('images/placeholder.png') }}" alt="{{ $track->title }}">
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-semibold">{{ $track->title }}</h3>
                            <p class="text-base-content/70">{{ $track->genres_list }}</p>
                            <div class="mt-2">
                                <a href="{{ $track->youtube_url }}" target="_blank" class="btn btn-sm btn-primary gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
                                        <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
                                    </svg>
                                    Watch on YouTube
                                </a>
                                <a href="{{ route('tracks.show', $track) }}" class="btn btn-sm btn-ghost gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    Track Details
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium">YouTube Video ID</p>
                            <p class="text-base-content/70">{{ $videoId }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Upload Date</p>
                            <p class="text-base-content/70">{{ $track->youtube_uploaded_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Video Stats -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Video Performance</h2>
                    
                    <div class="stats stats-vertical shadow w-full mt-4">
                        <div class="stat">
                            <div class="stat-figure text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </div>
                            <div class="stat-title">Total Views</div>
                            <div class="stat-value text-primary">{{ number_format($stats['viewCount']) }}</div>
                            <div class="stat-desc">Since {{ \Carbon\Carbon::parse($stats['publishedAt'])->format('M d, Y') }}</div>
                        </div>
                        
                        <div class="stat">
                            <div class="stat-figure text-success">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
                                </svg>
                            </div>
                            <div class="stat-title">Likes</div>
                            <div class="stat-value text-success">{{ number_format($stats['likeCount']) }}</div>
                            <div class="stat-desc">{{ $stats['viewCount'] > 0 ? round(($stats['likeCount'] / $stats['viewCount']) * 100, 2) . '% engagement rate' : 'No views yet' }}</div>
                        </div>
                        
                        <div class="stat">
                            <div class="stat-figure text-info">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                                </svg>
                            </div>
                            <div class="stat-title">Comments</div>
                            <div class="stat-value text-info">{{ number_format($stats['commentCount']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Video Preview -->
        <div class="mt-6">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Video Preview</h2>
                    
                    <div class="aspect-video w-full mt-4">
                        <iframe 
                            src="https://www.youtube.com/embed/{{ $videoId }}" 
                            title="{{ $track->title }}" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen
                            class="w-full h-full rounded-lg shadow-md"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 