@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary">{{ $genre->name }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('genres.edit', $genre->id) }}" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                </svg>
                Edit Genre
            </a>
            
            <form action="{{ route('playlists.create-from-genre', $genre->id) }}" method="POST" class="inline-block">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                    </svg>
                    Create Playlist
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title border-b pb-4 mb-4">Tracks in this Genre</h2>
            
            @if($tracks->isEmpty())
                <div class="text-gray-500">No tracks in this genre yet.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>
                                    <div class="flex items-center">
                                        Track
                                        <a href="{{ route('genres.show', ['genre' => $genre->id, 'sort' => 'name', 'order' => ($sortField == 'name' && $sortOrder == 'asc') ? 'desc' : 'asc']) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                        </a>
                                    </div>
                                </th>
                                <th>
                                    <div class="flex items-center">
                                        Duration
                                        <a href="{{ route('genres.show', ['genre' => $genre->id, 'sort' => 'duration', 'order' => ($sortField == 'duration' && $sortOrder == 'asc') ? 'desc' : 'asc']) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                        </a>
                                    </div>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tracks as $track)
                                <tr>
                                    <td>
                                        <div class="avatar">
                                            <div class="mask mask-squircle w-10 h-10">
                                                <img src="{{ $track->image_url }}" alt="{{ $track->title }}">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('tracks.show', $track) }}" class="link link-hover link-primary">{{ $track->title }}</a>
                                    </td>
                                    <td>{{ $track->duration ?? '0:00' }}</td>
                                    <td>
                                        <div class="flex gap-2 justify-end">
                                            <a href="{{ route('tracks.show', $track) }}" class="btn btn-circle btn-sm btn-ghost">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('tracks.play', $track) }}" class="btn btn-circle btn-sm btn-ghost text-success">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        </div>
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

    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title border-b pb-4 mb-4">Genre Information</h2>
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <tbody>
                        <tr>
                            <td class="font-medium w-40">Name:</td>
                            <td>{{ $genre->name }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium">Slug:</td>
                            <td>{{ $genre->slug }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium">Track Count:</td>
                            <td>{{ $tracks->total() }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium">Created:</td>
                            <td>{{ $genre->created_at->format('M d, Y') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('genres.index') }}" class="btn btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Back to Genres
        </a>
    </div>
</div>
@endsection
