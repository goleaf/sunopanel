@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">YouTube Login</h1>
        
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
                <h2 class="card-title mb-4">Enter YouTube Credentials</h2>
                
                <form action="{{ route('youtube.auth.save_credentials') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="email" class="block text-sm font-medium mb-2">Email Address:</label>
                        <input type="email" id="email" name="email" class="input input-bordered w-full" 
                               required value="{{ old('email') }}">
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="mt-4">
                        <label for="password" class="block text-sm font-medium mb-2">Password:</label>
                        <input type="password" id="password" name="password" class="input input-bordered w-full" required>
                        @error('password')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                <polyline points="10 17 15 12 10 7"></polyline>
                                <line x1="15" y1="12" x2="3" y2="12"></line>
                            </svg>
                            Save Credentials
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">YouTube Account Information</h2>
            
            <div class="mt-4">
                <div class="alert alert-info mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>Your YouTube credentials will be securely stored in your .env file. These are only used for uploading videos and managing playlists.</span>
                </div>
                
                <p class="mb-2"><strong>Important Notes:</strong></p>
                <ul class="list-disc list-inside ml-4 mb-4">
                    <li>We recommend using an app password rather than your main password for security</li>
                    <li>If your account has 2FA enabled, you must create an app password in your Google account settings</li>
                    <li>Make sure your account has permissions to upload videos to YouTube</li>
                </ul>
                
                <p class="text-sm text-base-content/70">
                    <a href="https://support.google.com/accounts/answer/185833" target="_blank" class="link link-primary">
                        How to create a Google app password →
                    </a>
                </p>
            </div>
        </div>
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
@endsection 