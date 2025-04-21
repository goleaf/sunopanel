@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold">Genres</h1>
                
                <!-- View Toggle -->
                <form method="GET" action="{{ route('genres.index') }}" class="ml-4">
                    <input type="hidden" name="view" value="{{ request('view', 'table') }}">
                    <div class="join">
                        <button type="button" onclick="setView('table')" class="join-item btn btn-sm {{ request('view', 'table') === 'table' ? 'btn-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 18h18M3 6h18" />
                            </svg>
                        </button>
                        <button type="button" onclick="setView('grid')" class="join-item btn btn-sm {{ request('view') === 'grid' ? 'btn-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
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
                    @if(request('view') === 'grid')
                    <!-- Grid View -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($genres as $genre)
                            <div class="card bg-base-200 shadow-md hover:shadow-xl transition-shadow duration-300">
                                <div class="card-body p-4">
                                    <h2 class="card-title text-lg">
                                        <a href="{{ route('genres.show', $genre) }}" class="link link-hover">
                                            {{ $genre->name }}
                                        </a>
                                    </h2>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="badge badge-primary">{{ $genre->tracks_count ?? 0 }} tracks</span>
                                        <span class="text-sm text-gray-500">{{ $genre->created_at->format('Y-m-d') }}</span>
                                    </div>
                                    <div class="card-actions justify-end mt-4">
                                        <a href="{{ route('genres.edit', $genre) }}" class="btn btn-sm btn-outline">
                                            Edit
                                        </a>
                                        <form action="{{ route('genres.destroy', $genre) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this genre?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-error">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @else
                    <!-- Table View -->
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
                    @endif
                    
                    <div class="mt-4">
                        {{ $genres->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function setView(viewType) {
            // Update the hidden input value
            document.querySelector('input[name="view"]').value = viewType;
            
            // Submit the form
            document.querySelector('form').submit();
        }
    </script>
@endsection 