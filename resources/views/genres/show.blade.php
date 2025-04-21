@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">{{ $genre->name }}</h1>
            <div class="flex space-x-2">
                <a href="{{ route('genres.edit', $genre) }}" class="btn btn-outline">Edit Genre</a>
                <a href="{{ route('genres.index') }}" class="btn btn-ghost">Back to Genres</a>
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title text-xl mb-4">Tracks in this Genre</h2>
                
                @if($tracks->isEmpty())
                    <div class="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>No tracks found in this genre.</span>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Artist</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tracks as $track)
                                    <tr>
                                        <td>{{ $track->title }}</td>
                                        <td>{{ $track->artist }}</td>
                                        <td>{{ $track->duration }}</td>
                                        <td>
                                            <a href="{{ route('tracks.show', $track) }}" class="btn btn-sm btn-outline">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $tracks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection 