<div>
    <x-heading :title="'Dashboard'" :breadcrumbs="['Dashboard']" />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        {{-- System Stats Section --}}
        <div class="lg:col-span-2">
            @livewire('system-stats')
        </div>

        {{-- Quick Actions Section --}}
        <x-card title="Quick Actions">
            <div class="flex flex-col space-y-2">
                <a href="{{ route('tracks.create') }}" class="btn btn-primary">Add New Track</a>
                <a href="{{ route('playlists.create') }}" class="btn btn-secondary">Create Playlist</a>
                <a href="{{ route('genres.create') }}" class="btn btn-accent">Add Genre</a>
            </div>
        </x-card>

        {{-- Navigation Section --}}
        <x-card title="Main Navigation">
            <div class="flex flex-col space-y-2">
                <a href="{{ route('tracks.index') }}" class="link link-hover">Manage Tracks</a>
                <a href="{{ route('genres.index') }}" class="link link-hover">Manage Genres</a>
                <a href="{{ route('playlists.index') }}" class="link link-hover">Manage Playlists</a>
            </div>
        </x-card>
    </div>
</div> 