<x-app-layout>
    <x-slot name="header">
        Add New Genre
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <div class="breadcrumbs text-sm">
                    <ul>
                        <li><a href="{{ route('genres.index') }}">Genres</a></li> 
                        <li>Add New Genre</li>
                    </ul>
                </div>
                <x-button href="{{ route('genres.index') }}" variant="ghost" size="sm">
                    <x-icon name="arrow-sm-left" size="4" class="mr-1" />
                    Back
                </x-button>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-xl">Create New Genre</h2>

                    <form wire:submit.prevent="save" class="space-y-6 mt-4">
                        <div class="form-control w-full">
                            <label for="name" class="label">
                                <span class="label-text font-medium">Name</span>
                            </label>
                            <input type="text" id="name" wire:model="name" 
                                class="input input-bordered w-full @error('name') input-error @enderror" />
                            @error('name') 
                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-control w-full">
                            <label for="description" class="label">
                                <span class="label-text font-medium">Description</span>
                            </label>
                            <textarea id="description" wire:model="description" 
                                class="textarea textarea-bordered h-24 w-full @error('description') textarea-error @enderror">
                            </textarea>
                            @error('description') 
                                <div class="text-error text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="flex justify-end space-x-2 mt-6">
                            <x-button href="{{ route('genres.index') }}" variant="outline" type="button">
                                Cancel
                            </x-button>
                            <x-button type="submit" variant="primary">
                                Create Genre
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
