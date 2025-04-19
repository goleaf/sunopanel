<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="card bg-base-100 overflow-hidden shadow-xl rounded-lg mb-8">
            <div class="card-body p-6">
                <!-- Welcome Section -->
                <div class="bg-base-200 rounded-lg p-6 mb-6 text-center">
                    <h1 class="text-3xl font-bold text-base-content mb-2">Welcome to SunoPanel</h1>
                    <p class="text-lg text-base-content/80">Your complete music management system</p>
                </div>
                
                <!-- System Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="stats shadow bg-primary text-primary-content">
                        <div class="stat">
                            <div class="stat-figure text-primary-content">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="stat-title">Total Tracks</div>
                            <div class="stat-value">{{ $stats['tracksCount'] }}</div>
                        </div>
                    </div>
                    
                    <div class="stats shadow bg-secondary text-secondary-content">
                        <div class="stat">
                            <div class="stat-figure text-secondary-content">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="stat-title">Genres</div>
                            <div class="stat-value">{{ $stats['genresCount'] }}</div>
                        </div>
                    </div>
                    
                    <div class="stats shadow bg-accent text-accent-content">
                        <div class="stat">
                            <div class="stat-figure text-accent-content">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                            </div>
                            <div class="stat-title">Playlists</div>
                            <div class="stat-value">{{ $stats['playlistsCount'] }}</div>
                            <div class="stat-desc">Music Collections</div>
                        </div>
                    </div>
                    
                    <div class="stats shadow bg-info text-info-content">
                        <div class="stat">
                            <div class="stat-figure text-info-content">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="stat-title">Total Duration</div>
                            <div class="stat-value">{{ $stats['totalDuration'] }}</div>
                            <div class="stat-desc">Minutes:Seconds</div>
                        </div>
                    </div>
                </div>
                
                <!-- Storage Usage Card -->
                <div class="card bg-base-200 mb-8">
                    <div class="card-body">
                        <h2 class="card-title mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                            </svg>
                            Storage Usage
                        </h2>
                        
                        <div class="flex flex-col">
                            <div class="mb-2 flex justify-between">
                                <span class="text-base-content/70">Used Space</span>
                                <span class="font-semibold">{{ $stats['storage'] }} MB</span>
                            </div>
                            
                            <progress class="progress progress-primary w-full" value="{{ min($stats['storage'], 1000) }}" max="1000"></progress>
                            
                            <div class="mt-1 text-xs text-base-content/60 text-right">
                                {{ $stats['storage'] }} MB used
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card bg-base-200 mb-8">
                    <div class="card-body">
                        <h2 class="card-title mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Quick Actions
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <a href="{{ route('tracks.create') }}" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add New Track
                            </a>
                            
                            <a href="{{ route('genres.create') }}" class="btn btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Genre
                            </a>
                            
                            <a href="{{ route('playlists.create') }}" class="btn btn-accent">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Playlist
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Main Navigation -->
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h2 class="card-title mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            Main Navigation
                        </h2>
                        
                        <ul class="menu bg-base-200 rounded-box w-full">
                            <li>
                                <a href="{{ route('tracks.index') }}" class="flex justify-between py-3 hover:bg-base-300">
                                    <div class="flex items-center">
                                        <span class="badge badge-primary mr-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                            </svg>
                                        </span>
                                        <span class="font-medium">Tracks</span>
                                    </div>
                                    <span class="text-sm opacity-70">Manage your music tracks collection</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('genres.index') }}" class="flex justify-between py-3 hover:bg-base-300">
                                    <div class="flex items-center">
                                        <span class="badge badge-secondary mr-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </span>
                                        <span class="font-medium">Genres</span>
                                    </div>
                                    <span class="text-sm opacity-70">Organize your music by genres</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('playlists.index') }}" class="flex justify-between py-3 hover:bg-base-300">
                                    <div class="flex items-center">
                                        <span class="badge badge-accent mr-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </span>
                                        <span class="font-medium">Playlists</span>
                                    </div>
                                    <span class="text-sm opacity-70">Create and manage playlists</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 