@props(['selectedGenre' => null])

@php
    $genres = \App\Models\Genre::orderBy('name')->get();
@endphp

<select name="genre_id" id="genre_id" {{ $attributes->merge(['class' => 'w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}>
    <option value="">-- Select Genre --</option>
    @foreach($genres as $genre)
        <option value="{{ $genre->id }}" {{ old('genre_id', $selectedGenre) == $genre->id ? 'selected' : '' }}>
            {{ $genre->name }}
        </option>
    @endforeach
</select> 