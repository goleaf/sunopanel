<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ theme: localStorage.getItem('theme') || 'light' }" x-init="$watch('theme', val => localStorage.setItem('theme', val))" :data-theme="theme">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sunopanel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <!-- Livewire Styles -->
    @livewireStyles
    <!-- Custom Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    {{-- Inline script to set theme immediately to avoid FOUC --}}
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body class="font-sans antialiased min-h-screen bg-base-200/50">
    <div class="min-h-screen flex flex-col">
        <!-- Top Navigation Bar -->
        <div class="bg-base-100 shadow-md sticky top-0 z-30">
            <div class="navbar container mx-auto">
                <!-- Logo and Brand -->
                <div class="flex-1">
                    <a href="{{ route('dashboard') }}" class="text-xl font-bold text-primary flex items-center">
                        <svg class="w-7 h-7 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        {{ config('app.name', 'Sunopanel') }}
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex">
                    <ul class="menu menu-horizontal px-1">
                        <li><a href="{{ route('dashboard') }}" @class(['font-medium', 'active' => request()->routeIs('dashboard')])>
                            <x-icon name="home" class="w-5 h-5" />
                            Dashboard
                        </a></li>
                        <li><a href="{{ route('tracks.index') }}" @class(['font-medium', 'active' => request()->routeIs('tracks.*')])>
                            <x-icon name="music-note" class="w-5 h-5" />
                            Tracks
                        </a></li>
                        <li><a href="{{ route('genres.index') }}" @class(['font-medium', 'active' => request()->routeIs('genres.*')])>
                            <x-icon name="tag" class="w-5 h-5" />
                            Genres
                        </a></li>
                        <li><a href="{{ route('playlists.index') }}" @class(['font-medium', 'active' => request()->routeIs('playlists.*')])>
                            <x-icon name="collection" class="w-5 h-5" />
                            Playlists
                        </a></li>
                    </ul>
                </div>
                
                <!-- Theme toggle and mobile menu button -->
                <div class="flex-none gap-2">
                    <x-theme-toggle class="btn btn-ghost btn-circle" />
                    <div class="dropdown dropdown-end md:hidden">
                        <label tabindex="0" class="btn btn-ghost btn-circle">
                            <x-icon name="menu" class="h-6 w-6" />
                        </label>
                        <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a href="{{ route('dashboard') }}" @class(['font-medium', 'active' => request()->routeIs('dashboard')])>
                                <x-icon name="home" class="w-5 h-5" />
                                Dashboard
                            </a></li>
                            <li><a href="{{ route('tracks.index') }}" @class(['font-medium', 'active' => request()->routeIs('tracks.*')])>
                                <x-icon name="music-note" class="w-5 h-5" />
                                Tracks
                            </a></li>
                            <li><a href="{{ route('genres.index') }}" @class(['font-medium', 'active' => request()->routeIs('genres.*')])>
                                <x-icon name="tag" class="w-5 h-5" />
                                Genres
                            </a></li>
                            <li><a href="{{ route('playlists.index') }}" @class(['font-medium', 'active' => request()->routeIs('playlists.*')])>
                                <x-icon name="collection" class="w-5 h-5" />
                                Playlists
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <main class="flex-grow p-4 md:p-6">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        <!-- Footer -->
        <footer class="footer footer-center p-4 bg-base-300 text-base-content">
            <aside>
                <p>Copyright © {{ date('Y') }} - All right reserved by {{ config('app.name', 'Sunopanel') }}</p>
            </aside>
        </footer>

        {{-- Notification Component --}}
        <div class="fixed top-5 right-5 z-50">
            <x-notification id="main-notification" />
        </div>
    </div>

    {{-- Flash message dispatching --}}
    <script>
        document.addEventListener('alpine:init', () => {
            @if (session('success'))
                Alpine.store('notifications').add("{{ session('success') }}", 'success');
            @endif
            @if (session('error'))
                Alpine.store('notifications').add("{{ session('error') }}", 'error');
            @endif
            @if (session('info'))
                Alpine.store('notifications').add("{{ session('info') }}", 'info');
            @endif
            @if (session('warning'))
                Alpine.store('notifications').add("{{ session('warning') }}", 'warning');
            @endif
        });
    </script>

    <!-- Livewire Scripts -->
    @livewireScripts
    <!-- Custom Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>

