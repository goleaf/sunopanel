@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">YouTube Integration</h1>
        
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title mb-4">Authentication Status</h2>
                

                
                <div class="mb-6">
                    @if ($isAuthenticated)
                        <div class="alert alert-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>You are authenticated with YouTube API.</span>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <span>You are not authenticated with YouTube API.</span>
                        </div>
                    @endif
                </div>
                
                <div class="card-actions">
                    @if ($isAuthenticated)
                        <a href="{{ route('youtube.upload.form') }}" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            Upload Videos to YouTube
                        </a>
                        <a href="{{ route('youtube.uploads') }}" class="btn btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="12" y1="3" x2="12" y2="21"></line>
                            </svg>
                            View Uploaded Videos
                        </a>
                        
                        <a href="{{ route('youtube.auth.login_form') }}" class="btn btn-outline">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Update Credentials
                        </a>
                    @else
                        <a href="{{ route('youtube.auth.login_form') }}" class="btn btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Enter API Credentials
                        </a>
                        
                        <a href="{{ route('youtube.auth.redirect') }}" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10 16L14 12L10 8"></path>
                                <path d="M14 12H3"></path>
                                <path d="M15 4H18C19.1046 4 20 4.89543 20 6V18C20 19.1046 19.1046 20 18 20H15"></path>
                            </svg>
                            Authenticate with YouTube API
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">YouTube Integration Features</h2>
            
            <div class="mt-4">
                <p class="mb-2">With YouTube integration, you can:</p>
                <ul class="list-disc list-inside ml-4 mb-4">
                    <li>Upload your completed tracks to YouTube</li>
                    <li>Automatically create playlists based on track genres</li>
                    <li>Add tracks to existing playlists</li>
                    <li>Control privacy settings (public, unlisted, private)</li>
                </ul>
                
                <p class="mb-2 text-warning"><strong>Important things to know:</strong></p>
                <ul class="list-disc list-inside ml-4">
                    <li>YouTube API quotas may limit the number of uploads per day</li>
                    <li>Authorization tokens expire periodically and may need renewal</li>
                    <li>You need to have a Google project with YouTube Data API v3 enabled</li>
                </ul>
            </div>
        </div>
    </div>
    
    @if ($isAuthenticated)
    <div class="mt-6">
        <form action="{{ route('youtube.test') }}" method="POST" class="inline-block">
            @csrf
            <button type="submit" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                </svg>
                Test YouTube Upload
            </button>
        </form>
    </div>
    @endif
</div>
@endsection 