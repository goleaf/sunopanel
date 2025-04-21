@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Genres</h1>
            <a href="{{ route('genres.create') }}" class="btn btn-primary">Create New Genre</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                @if($genres->isEmpty())
                    <div class="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>No genres found. Create your first genre to get started!</span>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Tracks</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($genres as $genre)
                                    <tr>
                                        <td>{{ $genre->id }}</td>
                                        <td>{{ $genre->name }}</td>
                                        <td>{{ $genre->tracks_count ?? 0 }}</td>
                                        <td>{{ $genre->created_at->format('Y-m-d') }}</td>
                                        <td class="flex space-x-2">
                                            <a href="{{ route('genres.edit', $genre) }}" class="btn btn-sm btn-outline">
                                                Edit
                                            </a>
                                            <form action="{{ route('genres.destroy', $genre) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this genre?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-error">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $genres->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection 