<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content">
            {{ __('Edit Genre') }}: {{ $genre->name }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-primary">Edit Genre: {{ $genre->name }}</h1>
            <x-button href="{{ route('genres.index') }}" color="ghost">
                <x-icon name="arrow-left" size="5" class="mr-2" />
                Back to Genres
            </x-button>
        </div>

        <x-genres-form 
            :genre="$genre" 
            :submitRoute="route('genres.update', $genre)" 
            submitMethod="PUT"
        />
    </div>
</x-app-layout>
