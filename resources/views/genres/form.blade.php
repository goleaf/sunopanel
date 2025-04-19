@extends('layouts.app')

@section('content')
{{-- Determine if we are editing or creating --}}
@php
    $isEditing = isset($genre) && $genre->exists;
    $formAction = $isEditing ? route('genres.update', $genre) : route('genres.store');
    $pageTitle = $isEditing ? 'Edit Genre: ' . Str::limit($genre->name, 50) : 'Add New Genre';
    $buttonText = $isEditing ? 'Update Genre' : 'Save Genre';
    $buttonIcon = $isEditing ? 'save' : 'plus';
@endphp

{{-- Page Header --}}
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold text-base-content truncate" @if($isEditing) title="{{ $genre->name }}" @endif>{{ $pageTitle }}</h1>
    <x-button 
        href="{{ route('genres.index') }}" 
        variant="outline"
        size="sm"
    >
        <x-icon name="arrow-sm-left" size="4" class="mr-1" />
        Back to Genres
    </x-button>
</div>

{{-- Form Card --}}
<div class="card bg-base-100 shadow-xl max-w-3xl mx-auto"> {{-- Centered form with max width --}}
    <form action="{{ $formAction }}" method="POST" class="card-body space-y-6">
        @csrf
        @if($isEditing)
            @method('PUT')
        @endif

        <h2 class="card-title text-lg">{{ $isEditing ? 'Genre Details' : 'Enter Genre Details' }}</h2>

        <x-form-validation-summary :errors="$errors" />

        {{-- Genre Name --}}
        <div class="form-control">
            <x-input
                id="name"
                name="name"
                type="text"
                label="Genre Name"
                value="{{ old('name', isset($genre) ? $genre?->name : null) }}"
                required
                helpText="Enter a unique genre name (e.g., Rock, Jazz, Electronic)"
                :error="$errors->first('name')"
            />
        </div>

        {{-- Genre Description --}}
        <div class="form-control">
            <x-textarea
                id="description"
                name="description"
                label="Description"
                rows="4"
                helpText="Provide a brief description of this genre (optional)"
                :error="$errors->first('description')"
            >{{ old('description', isset($genre) ? $genre?->description : null) }}</x-textarea> {{-- Use slot for value --}}
        </div>

        {{-- Action Buttons --}}
        <div class="card-actions justify-end pt-4">
             <x-button
                href="{{ route('genres.index') }}"
                variant="ghost"
                size="sm"
            >
                 <x-icon name="x-mark" class="mr-1 h-4 w-4" />
                Cancel
            </x-button>
            <x-tooltip text="{{ $isEditing ? 'Update' : 'Save' }} this genre" position="top">
                <x-button
                    type="submit"
                    variant="primary"
                    size="sm"
                >
                     <x-icon name="{{ $buttonIcon }}" class="mr-1 h-4 w-4" />
                    {{ $buttonText }}
                </x-button>
            </x-tooltip>
        </div>
    </form>
</div>
@endsection 