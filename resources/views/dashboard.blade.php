<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4">
        <div class="hero bg-base-200 rounded-lg mb-6 p-6">
            <div class="hero-content text-center">
                <div class="max-w-md">
                    <h1 class="text-3xl font-bold">Welcome to SunoPanel</h1>
                    <p class="py-4">Your music management system</p>
                </div>
            </div>
        </div>
        
        <!-- System Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="stats shadow bg-primary text-primary-content">
                <div class="stat">
                    <div class="stat-figure text-primary-content">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="stat-title">Total Tracks</div>
                    <div class="stat-value">{{ $stats['tracks'] }}</div>
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
                    <div class="stat-value">{{ $stats['genres'] }}</div>
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
                    <div class="stat-value">{{ $stats['playlists'] }}</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card bg-base-100 shadow-md mb-6">
            <div class="card-body">
                <h2 class="card-title">Quick Actions</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-3 gap-4 mt-4">
                    <a href="{{ route('tracks.create') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                        </svg>
                        Add Track
                    </a>
                    
                    <a href="{{ route('genres.create') }}" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Add Genre
                    </a>
                    
                    <a href="{{ route('playlists.create') }}" class="btn btn-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        New Playlist
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Navigation -->
        <div class="card bg-base-100 shadow-md">
            <div class="card-body">
                <h2 class="card-title mb-4">Main Navigation</h2>
                
                <ul class="menu bg-base-200 rounded-box">
                    <li>
                        <a href="{{ route('tracks.index') }}" class="flex justify-between">
                            <div class="flex items-center">
                                <span class="badge badge-primary mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                    </svg>
                                </span>
                                <span>Tracks</span>
                            </div>
                            <span class="text-sm opacity-70">Manage your music tracks collection</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('genres.index') }}" class="flex justify-between">
                            <div class="flex items-center">
                                <span class="badge badge-secondary mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </span>
                                <span>Genres</span>
                            </div>
                            <span class="text-sm opacity-70">Organize your music by genres</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('playlists.index') }}" class="flex justify-between">
                            <div class="flex items-center">
                                <span class="badge badge-accent mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </span>
                                <span>Playlists</span>
                            </div>
                            <span class="text-sm opacity-70">Create and manage playlists</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout> 