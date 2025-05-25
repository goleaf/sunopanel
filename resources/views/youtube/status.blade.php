@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">YouTube Integration Status</h1>
        
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title mb-4">Authentication Status</h2>
                

                
                <div class="mb-6">
                    @if ($isAuthenticated)
                        <div class="alert alert-success mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>You are authenticated with YouTube API.</span>
                        </div>
                        
                        <!-- Active Account Information -->
                        @if($activeAccount)
                        <div class="bg-base-200 p-4 rounded-lg mb-6">
                            <h3 class="text-lg font-semibold mb-2">Active YouTube Account</h3>
                            <div class="flex items-start gap-4">
                                @if(isset($channelInfo['thumbnails']['default']['url']))
                                    <img src="{{ $channelInfo['thumbnails']['default']['url'] }}" 
                                         alt="{{ $activeAccount->channel_name }}" 
                                         class="w-16 h-16 rounded-full">
                                @else
                                    <div class="w-16 h-16 rounded-full bg-red-600 flex items-center justify-center text-white text-2xl">
                                        <span>YT</span>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-bold">{{ $activeAccount->getDisplayName() }}</p>
                                    @if($activeAccount->channel_name && $activeAccount->channel_name !== $activeAccount->name)
                                        <p class="text-sm opacity-70">Channel: {{ $activeAccount->channel_name }}</p>
                                    @endif
                                    @if($activeAccount->email)
                                        <p class="text-sm opacity-70">{{ $activeAccount->email }}</p>
                                    @endif
                                    @if(isset($channelInfo['subscriberCount']))
                                        <div class="badge badge-primary mt-2">{{ number_format($channelInfo['subscriberCount']) }} subscribers</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <span>You are not authenticated with YouTube API. Add an account to get started.</span>
                        </div>
                    @endif
                </div>
                
                <!-- Accounts List -->
                @if(count($accounts) > 0)
                <div class="mb-6">
                    <h3 class="font-semibold text-lg mb-3">Your YouTube Accounts</h3>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Account Name</th>
                                    <th>Channel</th>
                                    <th>Last Used</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($accounts as $account)
                                <tr class="{{ $account->is_active ? 'bg-primary/10' : '' }}">
                                    <td>
                                        @if($account->is_active)
                                            <div class="badge badge-primary">Active</div>
                                        @endif
                                    </td>
                                    <td>{{ $account->name }}</td>
                                    <td>{{ $account->channel_name }}</td>
                                    <td>
                                        @if($account->last_used_at)
                                            {{ $account->last_used_at->diffForHumans() }}
                                        @else
                                            Never
                                        @endif
                                    </td>
                                    <td class="flex gap-2">
                                        @if(!$account->is_active)
                                        <form action="{{ route('youtube.auth.set-active') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="account_id" value="{{ $account->id }}">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                Activate
                                            </button>
                                        </form>
                                        @endif
                                        <form action="{{ route('youtube.auth.delete-account') }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this account?');">
                                            @csrf
                                            <input type="hidden" name="account_id" value="{{ $account->id }}">
                                            <button type="submit" class="btn btn-sm btn-error">
                                                Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                
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
                    @endif
                    
                    <!-- Add Account Button -->
                    <a href="{{ route('youtube.auth.redirect') }}" class="btn btn-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                        Add YouTube Account
                    </a>

                    <!-- Add Account With Name Form -->
                    <button onclick="document.getElementById('addAccountModal').showModal()" class="btn btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                        Add Named Account
                    </button>
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
                    <li>Manage multiple YouTube accounts and switch between them</li>
                </ul>
                
                <p class="mb-2 text-warning"><strong>Important things to know:</strong></p>
                <ul class="list-disc list-inside ml-4">
                    <li>YouTube API quotas may limit the number of uploads per day</li>
                    <li>Videos will be uploaded in the background via queue jobs</li>
                    <li>Authentication tokens expire periodically and may need renewal</li>
                    <li>Each account has its own upload quota and playlists</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add Account Modal -->
<dialog id="addAccountModal" class="modal">
    <div class="modal-box">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
        </form>
        <h3 class="font-bold text-lg mb-4">Add New YouTube Account</h3>
        <form action="{{ route('youtube.auth.redirect') }}" method="GET">
            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Account Name (optional)</span>
                </label>
                <input type="text" name="account_name" placeholder="Enter a name for this account" class="input input-bordered">
                <span class="label-text-alt mt-1">This helps you identify the account in the list</span>
            </div>
            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Proceed to YouTube Authorization</button>
            </div>
        </form>
    </div>
</dialog>
@endsection 