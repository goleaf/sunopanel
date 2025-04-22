@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold">Genres</h1>
                
                <!-- View Toggle -->
                <div class="ml-4">
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
                </div>
            </div>
            <a href="{{ route('genres.create') }}" class="btn btn-primary">Create New Genre</a>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-title">Total Genres</div>
                    <div class="stat-value">{{ $statistics['total_genres'] }}</div>
                </div>
            </div>
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-title">Total Tracks</div>
                    <div class="stat-value">{{ $statistics['total_tracks'] }}</div>
                </div>
            </div>
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-title">Genres with Tracks</div>
                    <div class="stat-value">{{ $statistics['genres_with_tracks'] }}</div>
                </div>
            </div>
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-title">Genres without Tracks</div>
                    <div class="stat-value">{{ $statistics['genres_without_tracks'] }}</div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <form method="GET" action="{{ route('genres.index') }}" class="flex flex-col md:flex-row gap-4">
                    <input type="hidden" name="view" value="{{ request('view', 'table') }}">
                    
                    <div class="form-control flex-1">
                        <div class="input-group">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search genres..." class="input input-bordered w-full" />
                            <button type="submit" class="btn btn-square">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-control">
                        <select name="sort" class="select select-bordered">
                            <option value="name" {{ $sortField === 'name' ? 'selected' : '' }}>Name</option>
                            <option value="tracks_count" {{ $sortField === 'tracks_count' ? 'selected' : '' }}>Track Count</option>
                            <option value="created_at" {{ $sortField === 'created_at' ? 'selected' : '' }}>Created At</option>
                        </select>
                    </div>
                    
                    <div class="form-control">
                        <select name="direction" class="select select-bordered">
                            <option value="asc" {{ $sortDirection === 'asc' ? 'selected' : '' }}>Ascending</option>
                            <option value="desc" {{ $sortDirection === 'desc' ? 'selected' : '' }}>Descending</option>
                        </select>
                    </div>
                    
                    <div class="form-control">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                    
                    @if(request('search') || request('sort') !== 'name' || request('direction') !== 'asc')
                        <div class="form-control">
                            <a href="{{ route('genres.index') }}" class="btn btn-outline">Clear Filters</a>
                        </div>
                    @endif
                </form>
            </div>
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
                                    <th>
                                        <div class="flex items-center">
                                            Name
                                            <a href="{{ route('genres.index', ['sort' => 'name', 'direction' => $sortField === 'name' && $sortDirection === 'asc' ? 'desc' : 'asc', 'search' => request('search'), 'view' => request('view')]) }}" class="ml-1">
                                                @if($sortField === 'name')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4" />
                                                    </svg>
                                                @endif
                                            </a>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="flex items-center">
                                            Tracks
                                            <a href="{{ route('genres.index', ['sort' => 'tracks_count', 'direction' => $sortField === 'tracks_count' && $sortDirection === 'asc' ? 'desc' : 'asc', 'search' => request('search'), 'view' => request('view')]) }}" class="ml-1">
                                                @if($sortField === 'tracks_count')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4" />
                                                    </svg>
                                                @endif
                                            </a>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="flex items-center">
                                            Created At
                                            <a href="{{ route('genres.index', ['sort' => 'created_at', 'direction' => $sortField === 'created_at' && $sortDirection === 'asc' ? 'desc' : 'asc', 'search' => request('search'), 'view' => request('view')]) }}" class="ml-1">
                                                @if($sortField === 'created_at')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4" />
                                                    </svg>
                                                @endif
                                            </a>
                                        </div>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($genres as $genre)
                                    <tr>
                                        <td>{{ $genre->id }}</td>
                                        <td>
                                            <a href="{{ route('genres.show', $genre) }}" class="link link-hover">
                                                {{ $genre->name }}
                                            </a>
                                        </td>
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
            // Get the current URL
            const url = new URL(window.location.href);
            
            // Update the view parameter
            url.searchParams.set('view', viewType);
            
            // Navigate to the new URL
            window.location.href = url.toString();
        }
    </script>
@endsection 