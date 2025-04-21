<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SunoPanel') }}</title>

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100">
    <!-- Navigation -->
    <div class="navbar bg-base-100 shadow-md justify-center">
        <div class="container">
            <div class="flex-1 flex items-center">
                <div class="dropdown">
                    <label tabindex="0" class="btn btn-ghost lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                        </svg>
                    </label>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                        <li><a href="{{ route('home.index') }}">Add</a></li>
                        <li><a href="{{ route('tracks.index') }}">Songs</a></li>
                        <li><a href="{{ route('genres.index') }}">Genres</a></li>
                        <li><a href="{{ route('videos.upload') }}" class="{{ request()->routeIs('videos*') ? 'active' : '' }}">Upload Video</a></li>
                        <li>
                            <a href="{{ route('youtube.status') }}" class="{{ request()->routeIs('youtube.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
                                    <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
                                </svg>
                                YouTube
                            </a>
                        </li>
                    </ul>
                </div>
                <a href="{{ route('home.index') }}" class="btn btn-ghost normal-case text-xl">SunoPanel</a>
                <div class="hidden lg:flex items-center ml-4">
                    <ul class="menu menu-horizontal px-1 items-start">
                        <li><a href="{{ route('home.index') }}" class="{{ request()->routeIs('home*') ? 'active' : '' }}">Add</a></li>
                        <li><a href="{{ route('tracks.index') }}" class="{{ request()->routeIs('tracks*') ? 'active' : '' }}">Songs</a></li>
                        <li><a href="{{ route('genres.index') }}" class="{{ request()->routeIs('genres*') ? 'active' : '' }}">Genres</a></li>
                        <li><a href="{{ route('videos.upload') }}" class="{{ request()->routeIs('videos*') ? 'active' : '' }}">Upload Video</a></li>
                        <li>
                            <a href="{{ route('youtube.status') }}" class="{{ request()->routeIs('youtube.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
                                    <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
                                </svg>
                                YouTube
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="alert alert-success shadow-lg max-w-4xl mx-auto mt-4">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-error shadow-lg max-w-4xl mx-auto mt-4">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Page Content -->
    <main class="py-4">
        @yield('content')
    </main>

    <!-- Scripts -->
    @stack('scripts')
</body>
</html> 