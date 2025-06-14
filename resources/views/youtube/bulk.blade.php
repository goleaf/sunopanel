@extends('layouts.app')

@section('title', 'YouTube Bulk Upload')

@section('head')
    {{-- JavaScript is included inline in this view --}}
@endsection

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                YouTube Bulk Upload
            </h1>
            <p class="text-gray-600 mt-1">Upload multiple tracks to YouTube at once</p>
        </div>
        
        <div class="flex gap-2 mt-4 md:mt-0">
            <a href="{{ route('youtube.status') }}" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Status
            </a>
            <button onclick="refreshData()" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Account Status -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                YouTube Account Status
            </h2>
            
            @if($activeAccount)
                <div class="flex items-center gap-2">
                    <div class="badge badge-success">Active</div>
                    <span class="font-medium">{{ $activeAccount->getDisplayName() }}</span>
                    @if($activeAccount->channel_name)
                        <span class="text-gray-500">({{ $activeAccount->channel_name }})</span>
                    @endif
                </div>
                
                @if($activeAccount->isTokenExpired())
                    <div class="alert alert-warning mt-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <span>Token expired. Please re-authenticate.</span>
                        <a href="{{ route('youtube.auth.redirect') }}" class="btn btn-sm btn-warning">Re-authenticate</a>
                    </div>
                @endif
            @else
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>No active YouTube account. Please authenticate first.</span>
                    <a href="{{ route('youtube.auth.redirect') }}" class="btn btn-sm btn-error">Authenticate</a>
                </div>
            @endif
        </div>
    </div>

    <!-- Queue Status -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Upload Queue Status
                <button onclick="refreshQueueStatus()" class="btn btn-xs btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </h2>
            
            <div id="queue-status" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="stat">
                    <div class="stat-title">Pending</div>
                    <div class="stat-value text-warning" id="pending-count">{{ $queueStatus['pending'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Processing</div>
                    <div class="stat-value text-info" id="processing-count">{{ $queueStatus['processing'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Completed</div>
                    <div class="stat-value text-success" id="completed-count">{{ $queueStatus['completed'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Failed</div>
                    <div class="stat-value text-error" id="failed-count">{{ $queueStatus['failed'] ?? 0 }}</div>
                </div>
            </div>
            
            @if(($queueStatus['failed'] ?? 0) > 0)
                <div class="mt-4">
                    <form action="{{ route('youtube.bulk.retry-failed') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Retry Failed Uploads
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Upload Form -->
    @if($activeAccount && !$activeAccount->isTokenExpired())
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    Upload Settings
                </h2>
                
                <form id="bulk-upload-form" class="space-y-4">
                    @csrf
                    
                    <!-- Account Selection -->
                    @if($accounts->count() > 1)
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">YouTube Account</span>
                            </label>
                            <select name="account_id" class="select select-bordered">
                                <option value="">Use Active Account ({{ $activeAccount->getDisplayName() }})</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" {{ $account->is_active ? 'selected' : '' }}>
                                        {{ $account->getDisplayName() }}
                                        @if($account->channel_name) ({{ $account->channel_name }}) @endif
                                        @if($account->is_active) - Active @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <!-- Hidden input for single account -->
                        <input type="hidden" name="account_id" value="{{ $activeAccount->id }}" />
                    @endif
                    
                    <!-- Upload Options -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Privacy Status</span>
                            </label>
                            <select name="privacy_status" class="select select-bordered" required>
                                <option value="unlisted" selected>Unlisted</option>
                                <option value="private">Private</option>
                                <option value="public">Public</option>
                            </select>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Category</span>
                            </label>
                            <select name="category_id" class="select select-bordered" required>
                                <option value="10" selected>Music</option>
                                <option value="1">Film & Animation</option>
                                <option value="2">Autos & Vehicles</option>
                                <option value="15">Pets & Animals</option>
                                <option value="17">Sports</option>
                                <option value="19">Travel & Events</option>
                                <option value="20">Gaming</option>
                                <option value="22">People & Blogs</option>
                                <option value="23">Comedy</option>
                                <option value="24">Entertainment</option>
                                <option value="25">News & Politics</option>
                                <option value="26">Howto & Style</option>
                                <option value="27">Education</option>
                                <option value="28">Science & Technology</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="cursor-pointer label">
                                <span class="label-text">Made for Kids</span>
                                <input type="checkbox" name="made_for_kids" class="checkbox checkbox-primary" />
                            </label>
                        </div>
                        
                        <div class="form-control">
                            <label class="cursor-pointer label">
                                <span class="label-text">YouTube Short</span>
                                <input type="checkbox" name="is_short" class="checkbox checkbox-primary" />
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Track Selection -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4">
                    <h2 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                        </svg>
                        Select Tracks to Upload
                        <span id="selected-count" class="badge badge-primary">0 selected</span>
                    </h2>
                    
                    <div class="flex gap-2 mt-4 md:mt-0">
                        <button onclick="selectAll()" class="btn btn-sm btn-outline">Select All</button>
                        <button onclick="selectNone()" class="btn btn-sm btn-outline">Select None</button>
                    </div>
                </div>
                
                <!-- Search -->
                <div class="form-control mb-4">
                    <div class="input-group">
                        <input type="text" id="track-search" placeholder="Search tracks..." class="input input-bordered flex-1" />
                        <button onclick="searchTracks()" class="btn btn-square">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Track List -->
                <div id="track-list" class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($eligibleTracks as $track)
                        <div class="flex items-center p-3 border rounded-lg hover:bg-base-200 transition-colors">
                            <input type="checkbox" name="track_ids[]" value="{{ $track->id }}" class="checkbox checkbox-primary mr-3 track-checkbox" onchange="updateSelectedCount()" />
                            <div class="flex-1">
                                <div class="font-medium">{{ $track->title }}</div>
                                <div class="text-sm text-gray-500">
                                    @if($track->genres->isNotEmpty())
                                        <span class="mr-4">{{ $track->genres_list }}</span>
                                    @endif
                                    @if($track->duration)
                                        <span class="mr-4">{{ $track->duration }}</span>
                                    @endif
                                    <span>{{ $track->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="badge badge-success">{{ $track->status }}</div>
                                @if($track->file_size_human)
                                    <div class="text-xs text-gray-500 mt-1">{{ $track->file_size_human }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                            </svg>
                            <p>No tracks available for upload</p>
                            <p class="text-xs">Tracks must be completed and have MP4 files to be eligible</p>
                        </div>
                    @endforelse
                </div>
                
                <!-- Action Buttons -->
                @if($eligibleTracks->isNotEmpty())
                    <div class="flex flex-col md:flex-row gap-2 mt-6">
                        <button onclick="queueUpload()" class="btn btn-primary flex-1" id="queue-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Queue for Upload
                        </button>
                        <button onclick="uploadNow()" class="btn btn-secondary flex-1" id="upload-now-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Upload Now (Max 10)
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

<script>
// Auto-refresh queue status every 30 seconds
setInterval(refreshQueueStatus, 30000);

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.track-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selected-count').textContent = `${count} selected`;
    
    // Update button states
    const queueBtn = document.getElementById('queue-btn');
    const uploadNowBtn = document.getElementById('upload-now-btn');
    
    if (queueBtn) queueBtn.disabled = count === 0;
    if (uploadNowBtn) {
        uploadNowBtn.disabled = count === 0 || count > 10;
        if (count > 10) {
            uploadNowBtn.textContent = `Upload Now (Max 10) - ${count} selected`;
        } else {
            uploadNowBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Upload Now (Max 10)
            `;
        }
    }
}

function selectAll() {
    document.querySelectorAll('.track-checkbox').forEach(cb => cb.checked = true);
    updateSelectedCount();
}

function selectNone() {
    document.querySelectorAll('.track-checkbox').forEach(cb => cb.checked = false);
    updateSelectedCount();
}

function refreshQueueStatus() {
    fetch('{{ route("youtube.bulk.queue-status") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('pending-count').textContent = data.pending || 0;
            document.getElementById('processing-count').textContent = data.processing || 0;
            document.getElementById('completed-count').textContent = data.completed || 0;
            document.getElementById('failed-count').textContent = data.failed || 0;
        })
        .catch(error => console.error('Error refreshing queue status:', error));
}

function refreshData() {
    location.reload();
}

function queueUpload() {
    const form = document.getElementById('bulk-upload-form');
    const formData = new FormData(form);
    
    // Add selected track IDs
    document.querySelectorAll('.track-checkbox:checked').forEach(cb => {
        formData.append('track_ids[]', cb.value);
    });
    
    // Ensure account_id is included (use empty string if not set)
    if (!formData.has('account_id')) {
        formData.append('account_id', '');
    }
    
    // Submit form
    const hiddenForm = document.createElement('form');
    hiddenForm.method = 'POST';
    hiddenForm.action = '{{ route("youtube.bulk.queue") }}';
    
    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                     document.querySelector('input[name="_token"]')?.value;
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        hiddenForm.appendChild(csrfInput);
    }
    
    for (let [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        hiddenForm.appendChild(input);
    }
    
    document.body.appendChild(hiddenForm);
    hiddenForm.submit();
}

function uploadNow() {
    const form = document.getElementById('bulk-upload-form');
    const formData = new FormData(form);
    
    // Add selected track IDs
    document.querySelectorAll('.track-checkbox:checked').forEach(cb => {
        formData.append('track_ids[]', cb.value);
    });
    
    // Ensure account_id is included (use empty string if not set)
    if (!formData.has('account_id')) {
        formData.append('account_id', '');
    }
    
    // Submit form
    const hiddenForm = document.createElement('form');
    hiddenForm.method = 'POST';
    hiddenForm.action = '{{ route("youtube.bulk.upload-now") }}';
    
    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                     document.querySelector('input[name="_token"]')?.value;
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        hiddenForm.appendChild(csrfInput);
    }
    
    for (let [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        hiddenForm.appendChild(input);
    }
    
    document.body.appendChild(hiddenForm);
    hiddenForm.submit();
}

// Initialize
updateSelectedCount();
</script>
@endsection 