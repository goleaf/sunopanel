@props([
    'route' => null,
    'placeholder' => 'Search...',
    'genres' => null,
    'showGenreFilter' => false,
    'perPage' => true
])

<form method="GET" action="{{ $route ?? request()->url() }}" class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4">
    <div class="w-full md:w-1/2">
        <x-search-input placeholder="{{ $placeholder }}" />
    </div>
    
    @if($showGenreFilter && $genres)
    <div class="w-full md:w-1/4">
        <x-select name="genre" class="block w-full">
            <option value="">All Genres</option>
            @foreach($genres as $genre)
                <option value="{{ $genre->id }}" {{ request('genre') == $genre->id ? 'selected' : '' }}>
                    {{ $genre->name }}
                </option>
            @endforeach
        </x-select>
    </div>
    @endif
    
    @if($perPage)
    <div class="w-full md:w-1/6">
        <x-per-page-selector :perPage="request('perPage', 10)" />
    </div>
    @endif
    
    <div>
        <x-button type="submit" color="indigo">
            <x-icon name="search" class="h-5 w-5 mr-1" />
            Filter
        </x-button>
    </div>
</form> 