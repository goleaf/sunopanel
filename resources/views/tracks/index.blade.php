<x-app-layout>
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-base-content">Tracks</h1>
        <div class="flex space-x-2">
             {{-- Search Form using DaisyUI join component --}}
             <form action="{{ route('tracks.index') }}" method="GET" class="join">
                <div> {{-- Wrapper div for input --}}
                    <input type="text" name="search" placeholder="Search title..." value="{{ request('search') }}" class="input input-bordered join-item input-sm w-48" />
                </div>
                 <select name="genre" class="select select-bordered join-item select-sm">
                     <option value="">All Genres</option>
                     @foreach($genres as $genre)
                         <option value="{{ $genre->id }}" {{ request('genre') == $genre->id ? 'selected' : '' }}>
                             {{ $genre->name }}
                         </option>
                     @endforeach
                 </select>
                 <div class="indicator"> {{-- Wrapper div for button --}}
                     <button type="submit" class="btn btn-primary join-item btn-sm">
                         <x-icon name="search" size="4" />
                     </button>
                 </div>
             </form>
             {{-- Add New Track Button --}}
             <x-button href="{{ route('tracks.create') }}" variant="primary" size="sm">
                <x-icon name="plus" size="4" class="mr-1" />
                 Add New Track
            </x-button>
        </div>
    </div>

    {{-- Main Content Card --}}
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            {{-- Removed inner flex container, handled by page header --}}
            {{-- Removed card-title, using page header --}}

            <div class="overflow-x-auto">
                {{-- Using DaisyUI table classes directly for clarity --}}
                <table class="table table-zebra table-sm w-full">
                    <thead>
                        <tr>
                            <th></th> {{-- Checkbox column --}}
                            <th>Title</th>
                            <th>Genres</th>
                            <th>Duration</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tracks as $track)
                            <tr>
                                <td>
                                    <label>
                                        <input type="checkbox" class="checkbox checkbox-primary checkbox-xs" />
                                    </label>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <div class="avatar">
                                            <div class="mask mask-squircle w-10 h-10">
                                                <img src="{{ $track->image_url }}" 
                                                     alt="{{ $track->title }}" 
                                                     onerror="this.src='{{ asset('images/no-image.jpg') }}'" />
                                            </div>
                                        </div>
                                        <div>
                                            <a href="{{ route('tracks.show', $track->id) }}" class="font-bold hover:text-primary transition duration-150 ease-in-out">
                                                {{ Str::limit($track->title, 40) }}
                                            </a>
                                            {{-- Optional: Add artist/album info here if available --}}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($track->genres->take(3) as $genre)
                                            <div class="badge badge-ghost badge-sm">{{ $genre->name }}</div>
                                        @endforeach
                                        @if($track->genres->count() > 3)
                                            <div class="badge badge-ghost badge-sm">...</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    {{ formatDuration($track->duration_seconds ?: $track->duration) }}
                                </td>
                                <td>
                                    <span title="{{ $track->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $track->created_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-1">
                                        <x-tooltip text="Play Track" position="top">
                                            <x-button href="{{ route('tracks.play', $track->id) }}" variant="ghost" size="xs" icon>
                                                <x-icon name="play" />
                                            </x-button>
                                        </x-tooltip>
                                        <x-tooltip text="Edit Track" position="top">
                                            <x-button href="{{ route('tracks.edit', $track->id) }}" variant="ghost" size="xs" icon>
                                                <x-icon name="pencil" />
                                            </x-button>
                                        </x-tooltip>
                                        <x-tooltip text="View Details" position="top">
                                            <x-button href="{{ route('tracks.show', $track->id) }}" variant="ghost" size="xs" icon>
                                                <x-icon name="eye" />
                                            </x-button>
                                        </x-tooltip>
                                        <x-tooltip text="Delete Track" position="top">
                                            <form action="{{ route('tracks.destroy', $track->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this track: {{ addslashes($track->title) }}?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" variant="ghost" color="error" size="xs" icon>
                                                    <x-icon name="trash" />
                                                </x-button>
                                            </form>
                                         </x-tooltip>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10">
                                    <div class="text-base-content/70">
                                        <p class="text-lg mb-2">No tracks found matching your criteria.</p>
                                        <x-button href="{{ route('tracks.create') }}" variant="primary" size="sm">
                                            <x-icon name="plus" class="mr-1" /> Add Your First Track
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                     {{-- Optional: Add table footer for bulk actions --}}
                    {{-- <tfoot>
                        <tr>
                            <th></th>
                            <th colspan="5">Bulk Actions Placeholder</th>
                        </tr>
                    </tfoot> --}}
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $tracks->links() }} {{-- Assumes default Laravel/Tailwind pagination views are styled --}}
            </div>
        </div>
    </div>
</x-app-layout>
