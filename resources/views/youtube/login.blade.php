@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">YouTube Login</h1>
        
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title mb-4">Enter YouTube API Credentials</h2>
                
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
                
                <form action="{{ route('youtube.save.credentials') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Client ID</span>
                        </label>
                        <input type="text" name="client_id" value="{{ $credential->client_id ?? old('client_id') }}" class="input input-bordered" required>
                        @error('client_id')
                            <span class="text-error text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Client Secret</span>
                        </label>
                        <input type="password" name="client_secret" value="{{ $credential->client_secret ?? old('client_secret') }}" class="input input-bordered" required>
                        @error('client_secret')
                            <span class="text-error text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Redirect URI</span>
                        </label>
                        <input type="text" name="redirect_uri" value="{{ $credential->redirect_uri ?? 'https://sunopanel.prus.dev/youtube-auth' }}" class="input input-bordered" required>
                        @error('redirect_uri')
                            <span class="text-error text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-control mt-6">
                        <button type="submit" class="btn btn-primary">Save Credentials</button>
                    </div>
                </form>
                
                <div class="mt-6">
                    <a href="{{ route('youtube.status') }}" class="btn btn-outline">
                        Back to YouTube Status
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">How to Get YouTube API Credentials</h2>
            
            <div class="mt-4">
                <ol class="list-decimal list-inside space-y-2">
                    <li>Go to the <a href="https://console.cloud.google.com/" target="_blank" class="link link-primary">Google Cloud Console</a></li>
                    <li>Create a new project or select an existing one</li>
                    <li>Navigate to APIs & Services > Library</li>
                    <li>Search for and enable the YouTube Data API v3</li>
                    <li>Go to APIs & Services > Credentials</li>
                    <li>Click Create Credentials > OAuth client ID</li>
                    <li>Set the application type to "Web application"</li>
                    <li>Add "https://sunopanel.prus.dev/youtube-auth" as an authorized redirect URI</li>
                    <li>Create the OAuth client</li>
                    <li>Copy the Client ID and Client Secret to the form above</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection 