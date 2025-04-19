@props([
    'type' => 'tracks', // tracks or playlists
    'route' => '#',
    'actions' => ['download', 'add-to-playlist', 'delete'],
    'count' => 0,
])

<div x-data="{
    selectedItems: [],
    action: '',
    playlistId: null,
    confirmDelete: false,
    init() {
        // Listen for item selection events
        window.addEventListener('item-selected', event => {
            const { id, selected } = event.detail;
            
            if (selected) {
                if (!this.selectedItems.includes(id)) {
                    this.selectedItems.push(id);
                }
            } else {
                this.selectedItems = this.selectedItems.filter(item => item !== id);
            }
        });
        
        // Listen for select all events
        window.addEventListener('select-all-items', event => {
            const { ids, selected } = event.detail;
            
            if (selected) {
                this.selectedItems = [...new Set([...this.selectedItems, ...ids])];
            } else {
                this.selectedItems = this.selectedItems.filter(id => !ids.includes(id));
            }
        });
    }
}" 
class="sticky bottom-0 z-10 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-3 shadow-lg transform transition-all duration-300"
:class="selectedItems.length > 0 ? 'translate-y-0' : 'translate-y-full'"
>
    <div class="container mx-auto flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center">
            <span class="text-sm font-medium">
                <span x-text="selectedItems.length"></span> {{ ucfirst($type) }} selected
            </span>
            <button 
                @click="selectedItems = []"
                class="text-xs text-blue-600 hover:underline ml-2"
            >
                Clear selection
            </button>
        </div>
        
        <div class="flex flex-wrap items-center gap-2">
            @if(in_array('download', $actions))
                <form :action="'{{ route('tracks.bulk-download') }}'" method="POST" x-show="selectedItems.length > 0">
                    @csrf
                    <template x-for="id in selectedItems">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <x-button 
                        type="submit"
                        variant="primary"
                        size="sm"
                        icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>'
                    >
                        Download
                    </x-button>
                </form>
            @endif
            
            @if(in_array('add-to-playlist', $actions) && $type === 'tracks')
                <div x-data="{ showPlaylistSelect: false }">
                    <x-button 
                        @click="showPlaylistSelect = !showPlaylistSelect"
                        variant="info"
                        size="sm"
                        icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>'
                    >
                        Add to Playlist
                    </x-button>
                    
                    <div 
                        x-show="showPlaylistSelect" 
                        @click.outside="showPlaylistSelect = false"
                        x-transition
                        class="absolute mt-2 bg-white dark:bg-gray-700 rounded-md shadow-lg p-3 border border-gray-200 dark:border-gray-600"
                    >
                        <form :action="'{{ route('playlists.add-tracks') }}'" method="POST">
                            @csrf
                            <template x-for="id in selectedItems">
                                <input type="hidden" name="track_ids[]" :value="id">
                            </template>
                            <div class="mb-3">
                                <label for="playlist_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Select Playlist
                                </label>
                                <select 
                                    name="playlist_id" 
                                    id="playlist_id"
                                    required
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                >
                                    <option value="">Select a playlist...</option>
                                    @foreach(\App\Models\Playlist::orderBy('title')->get() as $playlist)
                                        <option value="{{ $playlist->id }}">{{ $playlist->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <x-button 
                                    @click="showPlaylistSelect = false"
                                    type="button"
                                    variant="secondary"
                                    size="xs"
                                >
                                    Cancel
                                </x-button>
                                <x-button 
                                    type="submit"
                                    variant="primary"
                                    size="xs"
                                >
                                    Add
                                </x-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
            
            @if(in_array('delete', $actions))
                <div x-data="{ showConfirmDelete: false }">
                    <x-button 
                        @click="showConfirmDelete = true"
                        variant="danger"
                        size="sm"
                        icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>'
                    >
                        Delete
                    </x-button>
                    
                    <!-- Delete Confirmation Modal -->
                    <div 
                        x-show="showConfirmDelete" 
                        x-transition.opacity
                        class="fixed inset-0 z-50 overflow-y-auto" 
                        aria-labelledby="modal-title" 
                        role="dialog" 
                        aria-modal="true"
                    >
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div 
                                x-show="showConfirmDelete" 
                                x-transition.opacity
                                class="fixed inset-0 bg-gray-500 bg-opacity-75" 
                                aria-hidden="true"
                                @click="showConfirmDelete = false"
                            ></div>
                            
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                            
                            <div 
                                x-show="showConfirmDelete" 
                                x-transition
                                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6"
                            >
                                <div>
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900">
                                        <svg class="h-6 w-6 text-red-600 dark:text-red-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-5">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                            Delete Selected {{ ucfirst($type) }}
                                        </h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Are you sure you want to delete <span x-text="selectedItems.length"></span> {{ $type }}? This action cannot be undone.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                                    <form :action="'{{ route($type . '.bulk-delete') }}'" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <template x-for="id in selectedItems">
                                            <input type="hidden" name="ids[]" :value="id">
                                        </template>
                                        <x-button 
                                            type="submit"
                                            variant="danger"
                                            fullWidth
                                        >
                                            Delete
                                        </x-button>
                                    </form>
                                    <x-button 
                                        @click="showConfirmDelete = false"
                                        variant="secondary"
                                        fullWidth
                                    >
                                        Cancel
                                    </x-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div> 