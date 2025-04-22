@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">YouTube Integration Status</h1>
        
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title mb-4">Authentication Status</h2>
                
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
                
                <div class="mb-6">
                    @if ($isAuthenticated)
                        <div class="alert alert-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            @if ($useSimple)
                                <span>You are logged in to YouTube with your account credentials.</span>
                            @else
                                <span>You are authenticated with YouTube API.</span>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            @if ($useSimple)
                                <span>You need to provide your YouTube account credentials.</span>
                            @else
                                <span>You are not authenticated with YouTube API.</span>
                            @endif
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
                        
                        @if ($useSimple)
                            <a href="{{ route('youtube.auth.login_form') }}" class="btn btn-outline">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Update Credentials
                            </a>
                        @endif
                    @else
                        @if ($useSimple)
                            <a href="{{ route('youtube.auth.login_form') }}" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                    <polyline points="10 17 15 12 10 7"></polyline>
                                    <line x1="15" y1="12" x2="3" y2="12"></line>
                                </svg>
                                Login with YouTube Account
                            </a>
                        @else
                            <a href="{{ route('youtube.auth.redirect') }}" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 16L14 12L10 8"></path>
                                    <path d="M14 12H3"></path>
                                    <path d="M15 4H18C19.1046 4 20 4.89543 20 6V18C20 19.1046 19.1046 20 18 20H15"></path>
                                </svg>
                                Authenticate with YouTube API
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">YouTube Integration Information</h2>
            
            <div class="mt-4">
                <p class="mb-2">With YouTube integration, you can:</p>
                <ul class="list-disc list-inside ml-4 mb-4">
                    <li>Upload your completed tracks to YouTube</li>
                    <li>Automatically create playlists based on track genres</li>
                    <li>Add tracks to existing playlists</li>
                    <li>Control privacy settings (public, unlisted, private)</li>
                </ul>
                
                @if ($useSimple)
                    <div class="alert alert-info mt-4 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>
                            You're using the simple YouTube uploader with direct account login.
                            <br>This requires the youtube-upload CLI tool to be installed on your server.
                        </span>
                    </div>
                    
                    <p class="mb-2 text-warning"><strong>Important notes:</strong></p>
                    <ul class="list-disc list-inside ml-4">
                        <li>Use an app password instead of your main password for better security</li>
                        <li>YouTube may limit upload frequency on your account</li>
                        <li>Videos will be uploaded in the background via queue jobs</li>
                    </ul>
                @else
                    <p class="mb-2 text-warning"><strong>Important things to know:</strong></p>
                    <ul class="list-disc list-inside ml-4">
                        <li>YouTube API quotas may limit the number of uploads per day</li>
                        <li>Videos will be uploaded in the background via queue jobs</li>
                        <li>Authentication tokens expire periodically and may need renewal</li>
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection 