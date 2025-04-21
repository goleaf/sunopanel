@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Add Tracks</h1>
    
    <div class="card bg-base-200 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Paste tracks information below</h2>
            <p class="text-sm mb-4">Format: title.mp3|mp3_url|image_url|genres</p>
            
            <form action="{{ route('home.process') }}" method="POST">
                @csrf
                
                <div class="form-control">
                    <textarea
                        name="tracks_data"
                        class="textarea textarea-bordered h-64 font-mono"
                        placeholder="Fleeting Love (儚い愛).mp3|https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3|https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg|City pop,80s"
                    >{{ old('tracks_data') }}</textarea>
                    @error('tracks_data')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>
                
                <div class="card-actions justify-end mt-4">
                    <button type="submit" class="btn btn-primary">Process Tracks</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="mt-8">
        <h2 class="text-2xl font-bold mb-4">Instructions</h2>
        <div class="card bg-base-200 shadow-xl">
            <div class="card-body">
                <ol class="list-decimal list-inside space-y-2">
                    <li>Paste track information in the text area above, one track per line</li>
                    <li>Each line should follow the format: <code class="bg-base-300 px-2 py-1 rounded">title.mp3|mp3_url|image_url|genres</code></li>
                    <li>Multiple genres should be separated by commas</li>
                    <li>Click "Process Tracks" to start the import</li>
                    <li>Tracks will be processed in the background, you can view progress on the Songs page</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection 