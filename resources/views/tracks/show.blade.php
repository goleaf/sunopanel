@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <!-- Back button and heading -->
        <div class="flex justify-between items-center mb-6">
            <x-heading.h1>{{ $track->title }}</x-heading.h1>
            <x-button href="{{ route('tracks.index') }}" color="ghost">
                <x-icon name="back" size="5" class="mr-1" />
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
                                        <x-icon name="music" size="16" class="text-base-content/30" />
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
                                        <x-icon name="pencil" size="5" class="mr-1" />
                                        Edit Track
                                    </x-button>
                                    
                                    <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?');">
                                        @csrf
                                        @method('DELETE')
                                        <x-button 
                                            type="submit" 
                                            color="error"
                                        >
                                            <x-icon name="trash" size="5" class="mr-1" />
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
                                                                <img src="{{ $playlist->artwork_url }}" class="w-full h-full object-cover rounded-md" alt="{{ $playlist->title }}">
                                                            @else
                                                                <x-icon name="music" size="6" class="text-base-content/30" />
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <div class="font-semibold">{{ $playlist->title }}</div>
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
