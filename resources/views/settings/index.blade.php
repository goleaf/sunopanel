@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Application Settings</h1>
                    <p class="text-gray-600">Configure global settings and preferences for your SunoPanel</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="document.getElementById('resetModal').showModal()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reset to Defaults
                </button>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form action="{{ route('settings.update') }}" method="POST" class="space-y-8">
        @csrf
        
        <!-- YouTube Integration Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
                            <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">YouTube Integration</h2>
                        <p class="text-sm text-gray-600">Configure how tracks are displayed and filtered based on YouTube upload status</p>
                    </div>
                </div>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Visibility Filter -->
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-900 block mb-2">Track Visibility Filter</label>
                        <p class="text-sm text-gray-600 mb-4">Controls which tracks are displayed across all pages and listings</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="youtube_visibility_filter" value="all" 
                                   class="sr-only peer" 
                                   {{ $settings['youtube_visibility_filter'] === 'all' ? 'checked' : '' }}>
                            <div class="border-2 border-gray-200 rounded-lg p-4 transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 group-hover:border-gray-300">
                                <div class="flex items-center space-x-3">
                                    <div class="w-4 h-4 border-2 border-gray-300 rounded-full peer-checked:border-blue-500 peer-checked:bg-blue-500 relative">
                                        <div class="w-2 h-2 bg-white rounded-full absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100"></div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900">Show All Tracks</h3>
                                        <p class="text-sm text-gray-600">Display both uploaded and not uploaded tracks</p>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Default
                                    </span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="youtube_visibility_filter" value="uploaded" 
                                   class="sr-only peer" 
                                   {{ $settings['youtube_visibility_filter'] === 'uploaded' ? 'checked' : '' }}>
                            <div class="border-2 border-gray-200 rounded-lg p-4 transition-all duration-200 peer-checked:border-green-500 peer-checked:bg-green-50 group-hover:border-gray-300">
                                <div class="flex items-center space-x-3">
                                    <div class="w-4 h-4 border-2 border-gray-300 rounded-full peer-checked:border-green-500 peer-checked:bg-green-500 relative">
                                        <div class="w-2 h-2 bg-white rounded-full absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100"></div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900">Only Uploaded</h3>
                                        <p class="text-sm text-gray-600">Show only tracks uploaded to YouTube</p>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        Uploaded
                                    </span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="youtube_visibility_filter" value="not_uploaded" 
                                   class="sr-only peer" 
                                   {{ $settings['youtube_visibility_filter'] === 'not_uploaded' ? 'checked' : '' }}>
                            <div class="border-2 border-gray-200 rounded-lg p-4 transition-all duration-200 peer-checked:border-amber-500 peer-checked:bg-amber-50 group-hover:border-gray-300">
                                <div class="flex items-center space-x-3">
                                    <div class="w-4 h-4 border-2 border-gray-300 rounded-full peer-checked:border-amber-500 peer-checked:bg-amber-500 relative">
                                        <div class="w-2 h-2 bg-white rounded-full absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100"></div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900">Only Not Uploaded</h3>
                                        <p class="text-sm text-gray-600">Show only tracks not uploaded to YouTube</p>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Pending
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h4 class="font-medium text-blue-900">Global Filter Effect</h4>
                                <p class="text-sm text-blue-800 mt-1">This setting affects all track listings throughout the application including the tracks page, genre pages, search results, and dashboard statistics.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- YouTube Column Toggle -->
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-900 block mb-2">Display Options</label>
                        <p class="text-sm text-gray-600 mb-4">Configure what information is shown in track listings</p>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-900">Show YouTube Status Column</h3>
                                <p class="text-sm text-gray-600">Display upload status and YouTube link in track tables</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="show_youtube_column" value="1" 
                                   class="sr-only peer" 
                                   {{ $settings['show_youtube_column'] ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Current Settings Overview -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Current Configuration</h2>
                        <p class="text-sm text-gray-600">Overview of your current settings</p>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-medium text-blue-900">Track Visibility</h3>
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-blue-900 mb-1">
                            @if($settings['youtube_visibility_filter'] === 'all')
                                All Tracks
                            @elseif($settings['youtube_visibility_filter'] === 'uploaded')
                                Uploaded Only
                            @else
                                Not Uploaded Only
                            @endif
                        </div>
                        <p class="text-sm text-blue-700">
                            @if($settings['youtube_visibility_filter'] === 'all')
                                Showing both uploaded and pending tracks
                            @elseif($settings['youtube_visibility_filter'] === 'uploaded')
                                Showing only YouTube uploaded tracks
                            @else
                                Showing only tracks pending upload
                            @endif
                        </p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-medium text-green-900">YouTube Column</h3>
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-green-900 mb-1">
                            {{ $settings['show_youtube_column'] ? 'Visible' : 'Hidden' }}
                        </div>
                        <p class="text-sm text-green-700">
                            {{ $settings['show_youtube_column'] ? 'YouTube status column is displayed' : 'YouTube status column is hidden' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save Settings
            </button>
        </div>
    </form>
</div>

<!-- Reset Confirmation Modal -->
<div id="resetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div class="mt-4 text-center">
                <h3 class="text-lg font-medium text-gray-900">Reset Settings to Defaults</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to reset all settings to their default values? This action cannot be undone.
                    </p>
                </div>
                <div class="flex justify-center space-x-3 mt-4">
                    <button onclick="document.getElementById('resetModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-lg shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                    <form action="{{ route('settings.reset') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-lg shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            Reset Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Modal functionality
function showModal() {
    document.getElementById('resetModal').classList.remove('hidden');
}

function hideModal() {
    document.getElementById('resetModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('resetModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideModal();
    }
});
</script>
@endsection 