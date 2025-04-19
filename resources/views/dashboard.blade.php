@extends('layouts.app')

@section('content')
    <x-heading :title="'Dashboard'" :breadcrumbs="['Dashboard']" />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        {{-- Stats Section --}}
        <x-card title="System Statistics">
            <div class="stats shadow w-full">
                <div class="stat">
                    <div class="stat-title">Tracks</div>
                    <div class="stat-value">{{ $stats['tracksCount'] }}</div>
                    <div class="stat-desc">Total tracks</div>
                </div>

                <div class="stat">
                    <div class="stat-title">Genres</div>
                    <div class="stat-value">{{ $stats['genresCount'] }}</div>
                    <div class="stat-desc">Unique genres</div>
                </div>

                <div class="stat">
                    <div class="stat-title">Playlists</div>
                    <div class="stat-value">{{ $stats['playlistsCount'] }}</div>
                    <div class="stat-desc">Created playlists</div>
                </div>

                <div class="stat">
                    <div class="stat-title">Total Duration</div>
                    <div class="stat-value">{{ $stats['totalDuration'] }}</div>
                    <div class="stat-desc">Across all tracks</div>
                </div>
            </div>
        </x-card>

        {{-- Storage Section --}}
        <x-card title="Storage Usage">
            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path> {{-- Placeholder icon, replace if needed --}}
                    </svg>
                </div>
                <div class="stat-title">Track Storage</div>
                <div class="stat-value">{{ $stats['storage'] }} MB</div>
                <div class="stat-desc">Used by track files</div>
            </div>
        </x-card>

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
                {{-- <a href="{{ route('users.index') }}" class="link link-hover">Manage Users</a> --}}
            </div>
        </x-card>
    </div>

    {{-- Add more sections or widgets as needed --}}
    {{-- Example: Recent Activity --}}
    {{-- <x-card title="Recent Activity">
        <p>Placeholder for recent activity feed...</p>
    </x-card> --}}

@endsection 