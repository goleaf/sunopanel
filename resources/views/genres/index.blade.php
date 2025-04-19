<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content">
            {{ __('Genres') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4">
        @if (session('success'))
            <div class="alert alert-success mb-4">
                <x-icon name="check" size="6" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error mb-4">
                <x-icon name="x" size="6" />
                <span>{{ session('error') }}</span>
            </div>
        @endif
        
        <div class="card bg-base-100 shadow-md">
            <div class="card-body">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div class="w-full md:w-1/2">
                        <form method="GET" action="{{ route('genres.index') }}" class="join">
                            <input type="text" name="search" placeholder="Search genres..." value="{{ request('search') }}" class="input input-bordered join-item w-full" />
                            <x-button type="submit" color="primary" class="join-item">
                                <x-icon name="search" size="5" class="mr-1" />
                                Search
                            </x-button>
                        </form>
                    </div>
                    <div>
                        <x-button href="{{ route('genres.create') }}" color="primary">
                            <x-icon name="plus" size="5" class="mr-1" />
                            {{ __('Create Genre') }}
                        </x-button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>
                                    <a href="{{ route('genres.index', array_merge(request()->query(), [
                                        'sort' => 'name',
                                        'direction' => request('sort') === 'name' && request('direction') === 'asc' ? 'desc' : 'asc'
                                    ])) }}" class="flex items-center">
                                        {{ __('Name') }}
                                        @if(request('sort') === 'name')
                                            <x-icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" size="4" class="ml-1" />
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ route('genres.index', array_merge(request()->query(), [
                                        'sort' => 'tracks_count',
                                        'direction' => request('sort') === 'tracks_count' && request('direction') === 'asc' ? 'desc' : 'asc'
                                    ])) }}" class="flex items-center">
                                        {{ __('Tracks Count') }}
                                        @if(request('sort') === 'tracks_count')
                                            <x-icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" size="4" class="ml-1" />
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ route('genres.index', array_merge(request()->query(), [
                                        'sort' => 'created_at',
                                        'direction' => request('sort') === 'created_at' && request('direction') === 'asc' ? 'desc' : 'asc'
                                    ])) }}" class="flex items-center">
                                        {{ __('Created At') }}
                                        @if(request('sort') === 'created_at')
                                            <x-icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" size="4" class="ml-1" />
                                        @endif
                                    </a>
                                </th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($genres as $genre)
                                <tr>
                                    <td>
                                        <a href="{{ route('genres.show', $genre) }}" class="font-bold text-primary hover:underline">
                                            {{ $genre->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="badge">{{ $genre->tracks_count }}</div>
                                    </td>
                                    <td>
                                        {{ $genre->created_at->format('Y-m-d') }}
                                    </td>
                                    <td>
                                        <div class="flex flex-col md:flex-row gap-2">
                                            <x-button href="{{ route('genres.show', $genre) }}" color="info" size="xs">
                                                <x-icon name="eye" size="4" class="mr-1" />
                                                View
                                            </x-button>
                                            
                                            <x-button href="{{ route('genres.edit', $genre) }}" color="warning" size="xs">
                                                <x-icon name="pencil" size="4" class="mr-1" />
                                                Edit
                                            </x-button>
                                            
                                            <form method="POST" action="{{ route('genres.destroy', $genre) }}" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" color="error" size="xs" 
                                                    onclick="return confirm('{{ __('Are you sure you want to delete this genre?') }}')">
                                                    <x-icon name="trash" size="4" class="mr-1" />
                                                    Delete
                                                </x-button>
                                            </form>
                                            
                                            <x-button href="{{ route('playlists.create-from-genre', $genre) }}" color="accent" size="xs">
                                                <x-icon name="music" size="4" class="mr-1" />
                                                {{ __('Create Playlist') }}
                                            </x-button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="text-base-content/60">{{ __('No genres found') }}</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $genres->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
