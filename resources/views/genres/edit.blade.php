@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <x-page-header title="Edit Genre: {{ $genre->name }}">
        <x-slot name="buttons">
            <x-button href="{{ route('genres.index') }}" color="gray">
                <x-icon name="arrow-left" class="-ml-1 mr-2 h-5 w-5" />
                Back to Genres
            </x-button>
        </x-slot>
    </x-page-header>

    <x-genres-form 
        :genre="$genre" 
        :submitRoute="route('genres.update', $genre)" 
        submitMethod="PUT"
    />
</div>
@endsection
