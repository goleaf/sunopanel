@extends('layouts.app')

@section('title', 'Analytics Test')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-4">Analytics Test</h1>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Summary</h2>
        <pre>{{ json_encode($summary ?? [], JSON_PRETTY_PRINT) }}</pre>
        
        <h2 class="text-xl font-semibold mb-4 mt-6">Tracks Count</h2>
        <p>{{ $tracks->count() ?? 0 }} tracks found</p>
        
        <h2 class="text-xl font-semibold mb-4 mt-6">Stale Count</h2>
        <p>{{ $staleTracksCount ?? 0 }} stale tracks</p>
    </div>
</div>
@endsection 