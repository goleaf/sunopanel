<div>
    <x-heading :title="'Bulk Upload Tracks'" :breadcrumbs="['Tracks', 'Bulk Upload']" />

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Upload Multiple Audio Files</h2>
            
            @if (session()->has('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            @if ($uploadComplete)
                <div class="alert alert-info">
                    <div class="flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-6 h-6 mx-2 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <label>Upload complete! {{ $processedCount }} tracks uploaded successfully, {{ $failedCount }} failed.</label>
                    </div>
                </div>
            @endif
            
            <form wire:submit.prevent="processBulkUpload" class="space-y-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Select audio files (MP3, WAV, OGG)</span>
                    </label>
                    <input type="file" wire:model="files" class="file-input file-input-bordered w-full" multiple accept=".mp3,.wav,.ogg">
                    @error('files.*') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>
                
                @if ($files)
                    <div class="my-4">
                        <h3 class="font-semibold mb-2">Selected Files ({{ count($files) }})</h3>
                        <ul class="list-disc pl-5">
                            @foreach($files as $file)
                                <li>{{ $file->getClientOriginalName() }} ({{ round($file->getSize() / 1024 / 1024, 2) }} MB)</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Default Genre (optional)</span>
                    </label>
                    <select wire:model="defaultGenreId" class="select select-bordered w-full">
                        <option value="">-- No default genre --</option>
                        @foreach($genres as $genre)
                            <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="card-actions justify-end">
                    <a href="{{ route('tracks.index') }}" class="btn">Cancel</a>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="processBulkUpload">
                        <span wire:loading.remove wire:target="processBulkUpload">Upload Files</span>
                        <span wire:loading wire:target="processBulkUpload">
                            <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div> 