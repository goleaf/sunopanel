<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                        {{ $isEditing ? 'Edit Playlist: ' . $title : 'Create New Playlist' }}
                    </h1>

                    <form wire:submit.prevent="save">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Title -->
                            <div class="col-span-2">
                                <x-form.label for="title" :value="__('Title')" />
                                <x-form.input 
                                    id="title" 
                                    type="text"
                                    wire:model="title" 
                                    placeholder="Enter playlist title"
                                    required
                                />
                                @error('title') <x-form.error>{{ $message }}</x-form.error> @enderror
                            </div>

                            <!-- Description -->
                            <div class="col-span-2">
                                <x-form.label for="description" :value="__('Description')" />
                                <x-form.textarea 
                                    id="description" 
                                    wire:model="description" 
                                    placeholder="Enter playlist description (optional)"
                                    rows="4"
                                />
                                @error('description') <x-form.error>{{ $message }}</x-form.error> @enderror
                            </div>

                            <!-- Genre -->
                            <div>
                                <x-form.label for="genre_id" :value="__('Genre (Optional)')" />
                                <x-form.select id="genre_id" wire:model="genre_id">
                                    <option value="">Select a genre</option>
                                    @foreach($genres as $genre)
                                        <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                                    @endforeach
                                </x-form.select>
                                @error('genre_id') <x-form.error>{{ $message }}</x-form.error> @enderror
                            </div>

                            <!-- Cover Image URL -->
                            <div>
                                <x-form.label for="cover_image" :value="__('Cover Image URL (Optional)')" />
                                <x-form.input 
                                    id="cover_image" 
                                    type="text" 
                                    wire:model="cover_image" 
                                    placeholder="Enter cover image URL"
                                />
                                @error('cover_image') <x-form.error>{{ $message }}</x-form.error> @enderror
                            </div>

                            <!-- Visibility -->
                            <div class="col-span-2">
                                <div class="flex items-center mt-4">
                                    <x-form.checkbox id="is_public" wire:model="is_public" />
                                    <x-form.label for="is_public" class="ml-2" :value="__('Make this playlist public')" />
                                </div>
                                @error('is_public') <x-form.error>{{ $message }}</x-form.error> @enderror
                            </div>
                        </div>

                        <!-- Cover Image Preview -->
                        @if($cover_image)
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Cover Image Preview
                                </label>
                                <div class="mt-2">
                                    <img src="{{ $cover_image }}" 
                                         alt="Cover preview" 
                                         class="w-40 h-40 object-cover rounded-lg border dark:border-gray-600"
                                         onerror="this.src='{{ asset('images/no-playlist-image.png') }}'; this.onerror=null;">
                                </div>
                            </div>
                        @endif

                        <!-- Form Actions -->
                        <div class="flex justify-start space-x-4 mt-8">
                            <x-button type="submit" variant="primary">
                                {{ $isEditing ? __('Update Playlist') : __('Create Playlist') }}
                            </x-button>
                            <x-button href="{{ route('playlists.index') }}" variant="secondary">
                                Cancel
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript for handling alerts --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('alert', event => {
                const type = event.detail.type;
                const message = event.detail.message;
                
                // You can use your preferred alert/notification library here
                // This example assumes you have a notification component that accepts type and message
                if (window.showNotification) {
                    window.showNotification(type, message);
                } else {
                    alert(message); // Fallback to basic alert
                }
            });
        });
    </script>
</x-app-layout> 