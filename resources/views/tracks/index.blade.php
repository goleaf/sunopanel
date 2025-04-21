@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Songs</h1>
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-6">
        {{ session('success') }}
    </div>
    @endif

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if($tracks->isEmpty())
            <div class="alert">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>No tracks found. <a href="{{ route('home.index') }}" class="link link-primary">Add some tracks</a></span>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="table w-full">
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
                        @foreach($tracks as $track)
                        <tr data-track-id="{{ $track->id }}">
                            <td>
                                <a href="{{ route('tracks.show', $track) }}" class="font-medium text-blue-600 hover:underline">
                                    {{ $track->title }}
                                </a>
                            </td>
                            <td>{{ $track->genres_list }}</td>
                            <td class="track-status">
                                @if($track->status === 'completed')
                                <span class="badge badge-success">Completed</span>
                                @elseif($track->status === 'processing')
                                <span class="badge badge-warning">Processing</span>
                                @elseif($track->status === 'failed')
                                <span class="badge badge-error">Failed</span>
                                @else
                                <span class="badge badge-info">Pending</span>
                                @endif
                            </td>
                            <td>
                                <div class="track-progress">
                                    @if($track->status === 'processing')
                                    <progress class="progress progress-primary w-full" value="{{ $track->progress }}" max="100"></progress>
                                    <span class="text-xs text-right">{{ $track->progress }}%</span>
                                    @elseif($track->status === 'completed')
                                    <progress class="progress progress-success w-full" value="100" max="100"></progress>
                                    <span class="text-xs text-right">100%</span>
                                    @elseif($track->status === 'failed')
                                    <div class="tooltip" data-tip="{{ $track->error_message }}">
                                        <progress class="progress progress-error w-full" value="100" max="100"></progress>
                                    </div>
                                    @else
                                    <progress class="progress w-full" value="0" max="100"></progress>
                                    <span class="text-xs text-right">0%</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="flex space-x-2">
                                    <a href="{{ route('tracks.show', $track) }}" class="btn btn-sm btn-outline">
                                        View
                                    </a>
                                    <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-error">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $tracks->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@if(!$tracks->isEmpty())
<script>
document.addEventListener('DOMContentLoaded', function() {
    const trackRows = document.querySelectorAll('tr[data-track-id]');
    
    // Update function for tracks that are in progress
    function updateTrackStatus() {
        trackRows.forEach(row => {
            const trackId = row.getAttribute('data-track-id');
            const statusCell = row.querySelector('.track-status');
            const progressCell = row.querySelector('.track-progress');
            
            if (statusCell.textContent.trim().includes('Processing') || 
                statusCell.textContent.trim().includes('Pending')) {
                
                fetch(`/tracks/${trackId}/status`)
                    .then(response => response.json())
                    .then(data => {
                        // Update status
                        let statusHTML;
                        if (data.status === 'completed') {
                            statusHTML = '<span class="badge badge-success">Completed</span>';
                        } else if (data.status === 'processing') {
                            statusHTML = '<span class="badge badge-warning">Processing</span>';
                        } else if (data.status === 'failed') {
                            statusHTML = '<span class="badge badge-error">Failed</span>';
                        } else {
                            statusHTML = '<span class="badge badge-info">Pending</span>';
                        }
                        statusCell.innerHTML = statusHTML;
                        
                        // Update progress
                        let progressHTML;
                        if (data.status === 'processing') {
                            progressHTML = `
                                <progress class="progress progress-primary w-full" value="${data.progress}" max="100"></progress>
                                <span class="text-xs text-right">${data.progress}%</span>
                            `;
                        } else if (data.status === 'completed') {
                            progressHTML = `
                                <progress class="progress progress-success w-full" value="100" max="100"></progress>
                                <span class="text-xs text-right">100%</span>
                            `;
                        } else if (data.status === 'failed') {
                            progressHTML = `
                                <div class="tooltip" data-tip="${data.error_message}">
                                    <progress class="progress progress-error w-full" value="100" max="100"></progress>
                                </div>
                            `;
                        } else {
                            progressHTML = `
                                <progress class="progress w-full" value="0" max="100"></progress>
                                <span class="text-xs text-right">0%</span>
                            `;
                        }
                        progressCell.innerHTML = progressHTML;
                    })
                    .catch(error => console.error('Error updating track status:', error));
            }
        });
    }
    
    // Initially update status, then every 3 seconds
    updateTrackStatus();
    setInterval(updateTrackStatus, 3000);
});
</script>
@endif
@endsection 