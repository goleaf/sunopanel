@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Genres</h1>
    
    <div class="card bg-base-200 shadow-xl">
        <div class="card-body">
            @if ($genres->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($genres as $genre)
                        <a href="{{ route('genres.show', $genre) }}" class="card bg-base-100 hover:shadow-xl transition-shadow">
                            <div class="card-body">
                                <h2 class="card-title">{{ $genre->name }}</h2>
                                <p>{{ $genre->tracks_count }} {{ Str::plural('track', $genre->tracks_count) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
                
                <div class="mt-6">
                    {{ $genres->links() }}
                </div>
            @else
                <div class="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info flex-shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>No genres found. <a href="{{ route('home.index') }}" class="link link-primary">Add some tracks</a> to create genres.</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 