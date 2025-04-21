@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">{{ $genre->name }}</h1>
        <a href="{{ route('genres.index') }}" class="btn btn-outline">Back to Genres</a>
    </div>
    
    <div class="card bg-base-200 shadow-xl">
        <div class="card-body">
            <h2 class="card-title mb-4">Tracks in this genre</h2>
            
            @if ($tracks->count() > 0)
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tracks as $track)
                                <tr>
                                    <td>{{ $track->title }}</td>
                                    <td>
                                        @if ($track->status === 'completed')
                                            <span class="badge badge-success">Completed</span>
                                        @elseif ($track->status === 'processing')
                                            <span class="badge badge-warning">Processing</span>
                                        @elseif ($track->status === 'failed')
                                            <span class="badge badge-error">Failed</span>
                                        @else
                                            <span class="badge badge-info">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($track->status === 'processing')
                                            <progress class="progress progress-primary w-full" value="{{ $track->progress }}" max="100"></progress>
                                        @elseif ($track->status === 'completed')
                                            <progress class="progress progress-success w-full" value="100" max="100"></progress>
                                        @elseif ($track->status === 'failed')
                                            <div class="tooltip" data-tip="{{ $track->error_message }}">
                                                <progress class="progress progress-error w-full" value="100" max="100"></progress>
                                            </div>
                                        @else
                                            <progress class="progress w-full" value="0" max="100"></progress>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('tracks.show', $track) }}" class="btn btn-sm btn-outline">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-6">
                    {{ $tracks->links() }}
                </div>
            @else
                <div class="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info flex-shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>No tracks found in this genre. <a href="{{ route('home.index') }}" class="link link-primary">Add tracks</a> to this genre.</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 