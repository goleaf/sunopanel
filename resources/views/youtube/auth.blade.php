@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold">YouTube Integration</h1>
        <p class="mt-2 text-gray-600">Connect SunoPanel to your YouTube account to upload videos</p>
        <div class="mt-2">
            <a href="{{ route('youtube.config') }}" class="btn btn-sm">
                Detailed Configuration Instructions
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
        <!-- OAuth Authentication -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">YouTube API Authentication</h2>
                <p class="mb-4">Use Google OAuth to connect your YouTube account (recommended)</p>
                
                <div class="bg-base-200 p-4 rounded-lg mb-4">
                    <h3 class="font-semibold mb-2">Benefits:</h3>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>More reliable uploads</li>
                        <li>Playlist management</li>
                        <li>No need to enter password every time</li>
                        <li>Works with 2FA-enabled accounts</li>
                    </ul>
                </div>
                
                <div class="flex items-center mb-4">
                    <div class="flex-1 h-0.5 bg-base-300"></div>
                    <span class="px-3 text-sm text-base-content/70">Status</span>
                    <div class="flex-1 h-0.5 bg-base-300"></div>
                </div>
                
                @if($isAuthenticated)
                    <div class="alert alert-success mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Connected to YouTube via OAuth</span>
                    </div>
                @else
                    <div class="alert alert-warning mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <span>Not connected to YouTube via OAuth</span>
                    </div>
                @endif
                
                <div class="card-actions justify-center mt-4">
                    @if(!$isAuthenticated && $authUrl)
                        <a href="{{ $authUrl }}" class="btn btn-primary">
                            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21.582,6.186c-0.23-0.86-0.908-1.538-1.768-1.768C18.254,4,12,4,12,4S5.746,4,4.186,4.418 c-0.86,0.23-1.538,0.908-1.768,1.768C2,7.746,2,12,2,12s0,4.254,0.418,5.814c0.23,0.86,0.908,1.538,1.768,1.768 C5.746,20,12,20,12,20s6.254,0,7.814-0.418c0.861-0.23,1.538-0.908,1.768-1.768C22,16.254,22,12,22,12S22,7.746,21.582,6.186z M10,15.464V8.536L16,12L10,15.464z"/>
                            </svg>
                            Connect with Google
                        </a>
                    @else
                        <form action="{{ route('youtube.toggle.oauth') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn {{ $useOAuth ? 'btn-error' : 'btn-success' }}">
                                @if($useOAuth)
                                    Disable OAuth
                                @else
                                    Enable OAuth
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Simple Uploader Authentication -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Simple Authentication</h2>
                <p class="mb-4">Store your YouTube credentials for direct login (legacy method)</p>
                
                <div class="bg-base-200 p-4 rounded-lg mb-4">
                    <h3 class="font-semibold mb-2">Note:</h3>
                    <p class="text-sm text-base-content/80">
                        This method may become unreliable as Google updates their login process.
                        Consider using OAuth authentication instead.
                    </p>
                </div>
                
                <div class="flex items-center mb-4">
                    <div class="flex-1 h-0.5 bg-base-300"></div>
                    <span class="px-3 text-sm text-base-content/70">Status</span>
                    <div class="flex-1 h-0.5 bg-base-300"></div>
                </div>
                
                @if(config('youtube.email') && config('youtube.password'))
                    <div class="alert alert-success mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Credentials saved for {{ config('youtube.email') }}</span>
                    </div>
                @else
                    <div class="alert alert-warning mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <span>No credentials saved</span>
                    </div>
                @endif
                
                <div class="card-actions justify-center mt-4">
                    <a href="{{ route('youtube.auth.login_form') }}" class="btn btn-outline">
                        Set Credentials
                    </a>
                    
                    @if(config('youtube.email') && config('youtube.password'))
                        <form action="{{ route('youtube.toggle.simple') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn {{ $useSimple ? 'btn-error' : 'btn-success' }}">
                                @if($useSimple)
                                    Disable Simple Auth
                                @else
                                    Enable Simple Auth
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl mt-8">
        <div class="card-body">
            <h2 class="card-title">Configuration Steps</h2>
            
            <ol class="list-decimal pl-6 space-y-3 mt-4">
                <li>
                    <span class="font-semibold">Create OAuth Credentials:</span>
                    <ul class="list-disc pl-6 mt-1">
                        <li>Go to <a href="https://console.developers.google.com" target="_blank" class="link link-primary">Google Cloud Console</a></li>
                        <li>Create a new project (or select an existing one)</li>
                        <li>Enable the YouTube Data API v3</li>
                        <li>Create OAuth credentials (Web Application type)</li>
                        <li>Add authorized redirect URI: <code class="bg-base-200 px-2 py-1 rounded">{{ route('youtube.auth.callback') }}</code></li>
                    </ul>
                </li>
                <li>
                    <span class="font-semibold">Add your credentials to .env file:</span>
                    <pre class="bg-base-200 p-3 rounded mt-1">
YOUTUBE_CLIENT_ID=your_client_id_here
YOUTUBE_CLIENT_SECRET=your_client_secret_here
YOUTUBE_USE_OAUTH=true</pre>
                </li>
                <li>
                    <span class="font-semibold">Connect your YouTube account:</span>
                    <p class="mt-1">Click "Connect with Google" button above and authorize the application</p>
                </li>
            </ol>
        </div>
    </div>
</div>
@endsection 