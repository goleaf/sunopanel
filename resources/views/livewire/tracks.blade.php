<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Tracks') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ $editingTrackId ? __('Edit Track') : __('Add New Track') }}
                </h3>
                <form wire:submit.prevent="{{ $editingTrackId ? 'update' : 'create' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-form.label for="title">{{ __('Title') }}</x-form.label>
                            <x-form.input id="title" type="text" wire:model="title" placeholder="{{ __('Enter track title') }}" />
                            @error('title') <x-form.error>{{ $message }}</x-form.error> @enderror
                        </div>
                        <div>
                            <x-form.label for="artist">{{ __('Artist') }}</x-form.label>
                            <x-form.input id="artist" type="text" wire:model="artist" placeholder="{{ __('Enter artist name') }}" />
                            @error('artist') <x-form.error>{{ $message }}</x-form.error> @enderror
                        </div>
                        <div>
                            <x-form.label for="album">{{ __('Album') }}</x-form.label>
                            <x-form.input id="album" type="text" wire:model="album" placeholder="{{ __('Enter album name') }}" />
                            @error('album') <x-form.error>{{ $message }}</x-form.error> @enderror
                        </div>
                        <div>
                            <x-form.label for="genre_id">{{ __('Genre') }}</x-form.label>
                            <x-form.select id="genre_id" wire:model="genre_id">
                                <option value="">{{ __('Select Genre') }}</option>
                                @foreach($genres as $genre)
                                    <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                                @endforeach
                            </x-form.select>
                            @error('genre_id') <x-form.error>{{ $message }}</x-form.error> @enderror
                        </div>
                        <div>
                            <x-form.label for="audio_file">{{ __('Audio File') }}</x-form.label>
                            <input id="audio_file" type="file" wire:model="audio_file" accept="audio/mp3,audio/wav" class="file-input file-input-bordered w-full" />
                            @error('audio_file') <x-form.error>{{ $message }}</x-form.error> @enderror
                        </div>
                        <div>
                            <x-form.label for="image_url">{{ __('Image URL') }}</x-form.label>
                            <x-form.input id="image_url" type="text" wire:model="image_url" placeholder="{{ __('Enter image URL') }}" />
                            @error('image_url') <x-form.error>{{ $message }}</x-form.error> @enderror
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-2">
                        <button type="submit" class="btn btn-primary">
                            {{ $editingTrackId ? __('Update') : __('Create') }}
                        </button>
                        @if($editingTrackId)
                            <button type="button" class="btn btn-secondary" wire:click="resetInput">
                                {{ __('Cancel') }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Track List') }}</h3>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Artist') }}</th>
                                <th>{{ __('Album') }}</th>
                                <th>{{ __('Genre') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tracks as $track)
                                <tr>
                                    <td>{{ $track->title }}</td>
                                    <td>{{ $track->artist }}</td>
                                    <td>{{ $track->album ?? '-' }}</td>
                                    <td>{{ $track->genre->name ?? '-' }}</td>
                                    <td>
                                        <div class="flex space-x-2">
                                            <button class="btn btn-xs btn-success" wire:click="play({{ $track->id }})">{{ __('Play') }}</button>
                                            <button class="btn btn-xs btn-primary" wire:click="edit({{ $track->id }})">{{ __('Edit') }}</button>
                                            <button class="btn btn-xs btn-error" wire:click="delete({{ $track->id }})">{{ __('Delete') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">{{ __('No tracks found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 