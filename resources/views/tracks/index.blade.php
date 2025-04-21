@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Songs</h1>
    
    <div class="card bg-base-200 shadow-xl">
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Genres</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tracks as $track)
                            <tr>
                                <td>{{ $track->title }}</td>
                                <td>
                                    @foreach ($track->genres as $genre)
                                        <a href="{{ route('genres.show', $genre) }}" class="badge badge-primary mr-1">
                                            {{ $genre->name }}
                                        </a>
                                    @endforeach
                                </td>
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
                                    <div class="flex space-x-2">
                                        <a href="{{ route('tracks.show', $track) }}" class="btn btn-sm btn-outline">View</a>
                                        
                                        <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-error">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="alert">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info flex-shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span>No tracks found. <a href="{{ route('home.index') }}" class="link link-primary">Add some tracks</a> to get started.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $tracks->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-refresh tracks in processing status
    document.addEventListener('DOMContentLoaded', function() {
        const processingTracks = document.querySelectorAll('tr[data-status="processing"]');
        
        if (processingTracks.length > 0) {
            setInterval(function() {
                window.location.reload();
            }, 5000); // Refresh every 5 seconds
        }
    });
</script>
@endpush
@endsection 