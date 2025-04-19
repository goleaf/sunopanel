<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Genres') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ $editingGenreId ? __('Edit Genre') : __('Add New Genre') }}
                </h3>
                <form wire:submit.prevent="{{ $editingGenreId ? 'update' : 'create' }}">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <x-form.label for="name">{{ __('Name') }}</x-form.label>
                            <x-form.input id="name" type="text" wire:model="name" placeholder="{{ __('Enter genre name') }}" />
                            @error('name') <x-form.error>{{ $message }}</x-form.error> @enderror
                        </div>
                        <div>
                            <x-form.label for="description">{{ __('Description') }}</x-form.label>
                            <x-form.input id="description" type="text" wire:model="description" placeholder="{{ __('Enter genre description') }}" />
                            @error('description') <x-form.error>{{ $message }}</x-form.error> @enderror
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-2">
                        <button type="submit" class="btn btn-primary">
                            {{ $editingGenreId ? __('Update') : __('Create') }}
                        </button>
                        @if($editingGenreId)
                            <button type="button" class="btn btn-secondary" wire:click="resetInput">
                                {{ __('Cancel') }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Genre List') }}</h3>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($genres as $genre)
                                <tr>
                                    <td>{{ $genre->name }}</td>
                                    <td>{{ $genre->description ?? '-' }}</td>
                                    <td>
                                        <div class="flex space-x-2">
                                            <button class="btn btn-xs btn-primary" wire:click="edit({{ $genre->id }})">{{ __('Edit') }}</button>
                                            <button class="btn btn-xs btn-error" wire:click="delete({{ $genre->id }})">{{ __('Delete') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">{{ __('No genres found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 