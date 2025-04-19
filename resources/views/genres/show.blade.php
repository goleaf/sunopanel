@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <x-page-header title="{{ $genre->name }}">
        <x-slot name="buttons">
            <x-button href="{{ route('genres.edit', $genre->id) }}" color="blue" class="mr-2">
                <x-icon name="pencil" class="-ml-1 mr-2 h-5 w-5" />
                Edit Genre
            </x-button>
            
            <form action="{{ route('playlists.create-from-genre', $genre->id) }}" method="POST" class="inline-block">
                @csrf
                <x-button type="submit" color="green">
                    <x-icon name="collection" class="-ml-1 mr-2 h-5 w-5" />
                    Create Playlist
                </x-button>
            </form>
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <x-alert type="success" :message="session('success')" class="mb-4" />
    @endif

    <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-6">
        <div class="border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 px-6 py-4">Tracks in this Genre</h2>
        </div>

        @if($tracks->isEmpty())
            <div class="p-6 text-gray-500">No tracks in this genre yet.</div>
        @else
            <x-data-table
                :headers="[
                    'image' => ['label' => 'Image'],
                    'name' => ['label' => 'Track', 'sortable' => true],
                    'duration' => ['label' => 'Duration', 'sortable' => true],
                    'actions' => ['label' => 'Actions'],
                ]"
                :data="$tracks"
                :hasSearch="true"
                searchPlaceholder="Search tracks by name..."
                :searchRoute="route('genres.show', $genre->id)"
                :currentSort="$sortField ?? 'name'"
                :currentOrder="$sortOrder ?? 'asc'"
                :sortRoute="route('genres.show', $genre->id)"
                emptyMessage="No tracks found in this genre."
            >
                @foreach($tracks as $track)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <img src="{{ $track->image_url }}" alt="{{ $track->title }}" class="h-10 w-10 rounded-full object-cover">
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <a href="{{ route('tracks.show', $track) }}" class="hover:text-indigo-600">{{ $track->title }}</a>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">{{ $track->duration ?? '0:00' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex space-x-2 justify-end">
                                <a href="{{ route('tracks.show', $track) }}" class="text-indigo-600 hover:text-indigo-900">
                                    <x-icon name="eye" class="h-5 w-5" />
                                </a>
                                <a href="{{ route('tracks.play', $track) }}" class="text-green-600 hover:text-green-900">
                                    <x-icon name="play" class="h-5 w-5" />
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach

                <x-slot name="pagination">
                    {{ $tracks->links() }}
                </x-slot>
            </x-data-table>
        @endif
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-6">
        <div class="border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 px-6 py-4">Genre Information</h2>
        </div>
        <div class="p-6">
            <dl class="divide-y divide-gray-200">
                <div class="py-3 flex flex-col sm:flex-row">
                    <dt class="text-sm font-medium text-gray-500 sm:w-40 sm:flex-shrink-0">Name:</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:ml-6">{{ $genre->name }}</dd>
                </div>
                <div class="py-3 flex flex-col sm:flex-row">
                    <dt class="text-sm font-medium text-gray-500 sm:w-40 sm:flex-shrink-0">Slug:</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:ml-6">{{ $genre->slug }}</dd>
                </div>
                <div class="py-3 flex flex-col sm:flex-row">
                    <dt class="text-sm font-medium text-gray-500 sm:w-40 sm:flex-shrink-0">Track Count:</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:ml-6">{{ $tracks->total() }}</dd>
                </div>
                <div class="py-3 flex flex-col sm:flex-row">
                    <dt class="text-sm font-medium text-gray-500 sm:w-40 sm:flex-shrink-0">Created:</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:ml-6">{{ $genre->created_at->format('M d, Y') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-6">
        <x-button href="{{ route('genres.index') }}" color="gray" class="flex items-center">
            <x-icon name="chevron-left" class="mr-2 h-5 w-5" />
            Back to Genres
        </x-button>
    </div>
</div>
@endsection
