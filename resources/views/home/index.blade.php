@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">


    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title mb-4">Add New Tracks</h2>
            <p class="text-gray-600 mb-4">
                Enter tracks in the format: title.mp3|mp3_url|image_url|genres (comma-separated)
            </p>
            <form id="tracks-form" action="{{ route('home.process') }}" method="POST">
                @csrf

                <div class="form-control w-full mb-4">
                    <label for="tracks_input" class="label">
                        <span class="label-text">Enter Tracks (One per line)</span>
                    </label>
                    <textarea 
                        id="tracks_input" 
                        name="tracks_input" 
                        rows="10" 
                        class="textarea textarea-bordered w-full @error('tracks_input') textarea-error @enderror"
                        placeholder="Fleeting Love (儚い愛).mp3|https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3|https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg|City pop,80s"
                    >{{ old('tracks_input') }}</textarea>
                    @error('tracks_input')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                    @enderror
                </div>

                <div class="form-control mt-6 flex flex-row gap-4">
                    <button type="submit" class="btn btn-primary">
                        Queue Tracks (Background Processing)
                    </button>
                    <button type="button" id="process-immediate-btn" class="btn btn-secondary">
                        Process Immediately (Check Failures)
                    </button>
                </div>
                
                <div class="mt-4 text-sm text-gray-600">
                    <p><strong>Queue Tracks:</strong> Faster response, processes tracks in the background. Check status on the Songs page.</p>
                    <p><strong>Process Immediately:</strong> Slower response, but provides immediate feedback on errors. Page will not respond until processing completes.</p>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Instructions</h2>
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h3 class="font-bold mb-2">Format</h3>
                <p class="mb-4">Each track should be on its own line with the following format:</p>
                <pre class="bg-base-200 p-4 rounded-lg overflow-x-auto mb-4">title.mp3|mp3_url|image_url|genre1,genre2,genre3</pre>
                
                <h3 class="font-bold mb-2">Example</h3>
                <pre class="bg-base-200 p-4 rounded-lg overflow-x-auto mb-4">Fleeting Love (儚い愛).mp3|https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3|https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg|City pop,80s</pre>
                
                <h3 class="font-bold mb-2">Process</h3>
                <ol class="list-decimal list-inside space-y-2 mb-4">
                    <li>The system will download the MP3 and image files</li>
                    <li>The MP3 and image will be combined to create an MP4 video</li>
                    <li>The genres will be processed (created if they don't exist)</li>
                    <li>You can monitor the progress on the Songs page</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('tracks-form');
        const immediateBtn = document.getElementById('process-immediate-btn');
        
        immediateBtn.addEventListener('click', function() {
            // Change form action to immediate processing route
            form.action = "{{ route('home.process.immediate') }}";
            form.submit();
        });
    });
</script>
@endpush
@endsection 