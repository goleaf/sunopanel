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
                    
                    <div class="mt-6 flex justify-end space-x-2">
                        <a href="{{ route('genres.index') }}" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Genre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection 