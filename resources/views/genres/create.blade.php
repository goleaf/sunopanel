@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Create Genre</h1>
            <a href="{{ route('genres.index') }}" class="btn btn-outline">Back to Genres</a>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <form action="{{ route('genres.store') }}" method="POST">
                    @csrf
                    
                    <div class="form-control w-full">
                        <label for="name" class="label">
                            <span class="label-text">Genre Name</span>
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="input input-bordered w-full @error('name') input-error @enderror" 
                            value="{{ old('name') }}" 
                            required
                        />
                        @error('name')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>
                    
                    <div class="form-control w-full mt-4">
                        <label for="genre_id" class="label">
                            <span class="label-text">Genre ID (UUID format)</span>
                        </label>
                        <input 
                            type="text" 
                            id="genre_id" 
                            name="genre_id" 
                            class="input input-bordered w-full @error('genre_id') input-error @enderror" 
                            value="{{ old('genre_id') }}" 
                            placeholder="e.g. 123e4567-e89b-12d3-a456-426614174000"
                        />
                        @error('genre_id')
                            <label class="label">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </label>
                        @enderror
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-2">
                        <a href="{{ route('genres.index') }}" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Genre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection 