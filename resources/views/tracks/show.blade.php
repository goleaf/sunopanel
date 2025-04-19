@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <!-- Back button and heading -->
        <div class="flex justify-between items-center mb-6">
            <x-heading.h1>{{ $track->title }}</x-heading.h1>
            <x-button href="{{ route('tracks.index') }}" color="ghost">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                Back to Tracks
            </x-button>
        </div>

        <!-- Success/Error messages -->
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Track Information -->
            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-lg">
                    <div class="card-body">
                        <div class="flex flex-col md:flex-row gap-6">
                            <!-- Album Art -->
                            <div class="w-full md:w-48 flex-shrink-0">
                                @if($track->artwork_url)
                                    <img src="{{ $track->artwork_url }}" alt="{{ $track->title }}" class="w-full h-auto rounded-lg shadow-md">
                                @else
                                    <div class="w-full aspect-square bg-base-200 rounded-lg shadow-md flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <!-- Track Details -->
                            <div class="flex-1">
                                <h2 class="text-xl font-bold mb-2">{{ $track->title }}</h2>
                                
                                <div class="mb-4">
                                    @if($track->genres->count() > 0)
                                        <div class="flex flex-wrap gap-2 mb-2">
                                            @foreach($track->genres as $genre)
                                                <span class="badge badge-primary">{{ $genre->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Media Player -->
                                <div class="mb-4">
                                    <x-audio-player :track="$track" />
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex flex-wrap gap-2">
                                    <x-button 
                                        href="{{ route('tracks.edit', $track) }}" 
                                        color="primary"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                        Edit Track
                                    </x-button>
                                    
                                    <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?');">
                                        @csrf
                                        @method('DELETE')
                                        <x-button 
                                            type="submit" 
                                            color="error"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                            Delete Track
                                        </x-button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Details -->
                <div class="card bg-base-100 shadow-lg mt-8">
                    <div class="card-body">
                        <h2 class="card-title">Media Details</h2>
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <tbody>
                                    <tr>
                                        <td class="font-semibold">Title</td>
                                        <td>{{ $track->title }}</td>
                                    </tr>
                                    @if($track->artist)
                                    <tr>
                                        <td class="font-semibold">Artist</td>
                                        <td>{{ $track->artist }}</td>
                                    </tr>
                                    @endif
                                    @if($track->album)
                                    <tr>
                                        <td class="font-semibold">Album</td>
                                        <td>{{ $track->album }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td class="font-semibold">File Path</td>
                                        <td>{{ $track->file_path }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-semibold">File Size</td>
                                        <td>{{ $track->formatted_file_size }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-semibold">Duration</td>
                                        <td>{{ $track->formatted_duration }}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-semibold">Uploaded</td>
                                        <td>{{ $track->created_at->format('M d, Y \a\t h:i A') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Playlists -->
            <div class="lg:col-span-1">
                <div class="card bg-base-100 shadow-lg">
                    <div class="card-body">
                        <h2 class="card-title flex justify-between items-center">
                            <span>Playlists</span>
                            <span class="badge badge-primary">{{ $track->playlists->count() }}</span>
                        </h2>
                        @if($track->playlists->isEmpty())
                            <div class="text-center py-4">
                                <p class="text-base-content/60">This track is not in any playlists yet.</p>
                                <x-button 
                                    href="{{ route('playlists.create') }}" 
                                    color="primary"
                                    class="mt-4"
                                >
                                    Create a Playlist
                                </x-button>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <tbody>
                                        @foreach($track->playlists as $playlist)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('playlists.show', $playlist) }}" class="flex items-center">
                                                        <div class="w-10 h-10 bg-base-200 rounded-md flex items-center justify-center mr-3">
                                                            @if($playlist->artwork_url)
                                                                <img src="{{ $playlist->artwork_url }}" class="w-full h-full object-cover rounded-md" alt="{{ $playlist->name }}">
                                                            @else
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                                                </svg>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <div class="font-semibold">{{ $playlist->name }}</div>
                                                            <div class="text-xs text-base-content/60">{{ $playlist->tracks->count() }} tracks</div>
                                                        </div>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
