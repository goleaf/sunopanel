@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Global Settings
        </h1>
        <p class="text-sm text-base-content/70">Configure global application settings that affect all pages</p>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title mb-6">YouTube Integration Settings</h2>
            
            <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- YouTube Visibility Filter -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">YouTube Upload Visibility Filter</span>
                        <span class="label-text-alt">Controls which tracks are shown in all listings</span>
                    </label>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="youtube_visibility_filter" value="all" 
                                   class="radio radio-primary" 
                                   {{ $settings['youtube_visibility_filter'] === 'all' ? 'checked' : '' }}>
                            <div class="card bg-base-200 shadow-sm hover:shadow-md transition-shadow duration-200 ml-3">
                                <div class="card-body p-4">
                                    <h3 class="card-title text-sm">Show All Tracks</h3>
                                    <p class="text-xs text-base-content/70">Display both uploaded and not uploaded tracks</p>
                                </div>
                            </div>
                        </label>
                        
                        <label class="cursor-pointer">
                            <input type="radio" name="youtube_visibility_filter" value="uploaded" 
                                   class="radio radio-success" 
                                   {{ $settings['youtube_visibility_filter'] === 'uploaded' ? 'checked' : '' }}>
                            <div class="card bg-base-200 shadow-sm hover:shadow-md transition-shadow duration-200 ml-3">
                                <div class="card-body p-4">
                                    <h3 class="card-title text-sm">Only Uploaded</h3>
                                    <p class="text-xs text-base-content/70">Show only tracks uploaded to YouTube</p>
                                </div>
                            </div>
                        </label>
                        
                        <label class="cursor-pointer">
                            <input type="radio" name="youtube_visibility_filter" value="not_uploaded" 
                                   class="radio radio-warning" 
                                   {{ $settings['youtube_visibility_filter'] === 'not_uploaded' ? 'checked' : '' }}>
                            <div class="card bg-base-200 shadow-sm hover:shadow-md transition-shadow duration-200 ml-3">
                                <div class="card-body p-4">
                                    <h3 class="card-title text-sm">Only Not Uploaded</h3>
                                    <p class="text-xs text-base-content/70">Show only tracks not uploaded to YouTube</p>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h3 class="font-bold">Global Filter</h3>
                            <div class="text-sm">This setting affects all track listings throughout the application including tracks page, genre pages, and search results.</div>
                        </div>
                    </div>
                </div>
                
                <!-- Show YouTube Column -->
                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text font-semibold">Show YouTube Status Column</span>
                        <input type="checkbox" name="show_youtube_column" value="1" 
                               class="toggle toggle-primary" 
                               {{ $settings['show_youtube_column'] ? 'checked' : '' }}>
                    </label>
                    <div class="label">
                        <span class="label-text-alt">When enabled, shows YouTube upload status in track listings</span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="card-actions justify-between pt-6">
                    <button type="button" onclick="document.getElementById('resetModal').showModal()" class="btn btn-outline btn-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset to Defaults
                    </button>
                    
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Current Settings Summary -->
    <div class="card bg-base-100 shadow-xl mt-6">
        <div class="card-body">
            <h2 class="card-title mb-4">Current Settings Summary</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="stat bg-base-200 rounded-lg">
                    <div class="stat-title">YouTube Visibility</div>
                    <div class="stat-value text-lg">
                        @if($settings['youtube_visibility_filter'] === 'all')
                            <span class="badge badge-primary">All Tracks</span>
                        @elseif($settings['youtube_visibility_filter'] === 'uploaded')
                            <span class="badge badge-success">Uploaded Only</span>
                        @else
                            <span class="badge badge-warning">Not Uploaded Only</span>
                        @endif
                    </div>
                    <div class="stat-desc">Global filter for track listings</div>
                </div>
                
                <div class="stat bg-base-200 rounded-lg">
                    <div class="stat-title">YouTube Column</div>
                    <div class="stat-value text-lg">
                        @if($settings['show_youtube_column'])
                            <span class="badge badge-success">Visible</span>
                        @else
                            <span class="badge badge-error">Hidden</span>
                        @endif
                    </div>
                    <div class="stat-desc">YouTube status column visibility</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<dialog id="resetModal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg">Reset Settings to Defaults</h3>
        <p class="py-4">Are you sure you want to reset all settings to their default values? This action cannot be undone.</p>
        <div class="modal-action">
            <form method="dialog">
                <button class="btn">Cancel</button>
            </form>
            <form action="{{ route('settings.reset') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-warning">Reset Settings</button>
            </form>
        </div>
    </div>
</dialog>
@endsection 