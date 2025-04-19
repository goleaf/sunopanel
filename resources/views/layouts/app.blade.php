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

    {{-- Inline script to set theme immediately to avoid FOUC --}}
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body class="font-sans antialiased min-h-screen bg-base-200/50"> {{-- Changed background for subtle contrast --}}
    {{-- Using lg drawer variant for persistent sidebar on large screens --}}
    <div class="drawer lg:drawer-open">
        <input id="drawer-toggle" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col items-stretch">
            <!-- Navbar -->
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                {{-- Drawer Toggle (Mobile) --}}
                <div class="flex-none lg:hidden">
                    <label for="drawer-toggle" class="btn btn-square btn-ghost" aria-label="Open menu">
                        <x-icon name="menu" class="inline-block w-5 h-5 stroke-current" />
                    </label>
                </div>
                {{-- App Name / Logo --}}
                <div class="flex-1 px-2 mx-2">
                    <a href="{{ route('dashboard') }}" class="text-lg font-bold text-primary">
                        {{ config('app.name', 'Sunopanel') }}
                    </a>
                </div>
                {{-- Navbar actions (Desktop) --}}
                <div class="flex-none hidden lg:flex items-center gap-4">
                    {{-- Theme Toggle --}}
                    <x-theme-toggle />
                </div>
            </div>

            <!-- Page Content -->
            {{-- Added consistent padding --}}
            <main class="flex-grow p-6">
                 {{-- Removed max-width-7xl mx-auto wrapper - let content decide max width --}}
                 {{-- Removed duplicate session alerts, handled by notification component --}}
                 @hasSection('content')
                     @yield('content')
                 @else
                     {{ $slot ?? '' }}
                 @endif
            </main>

            <!-- Footer -->
            <footer class="footer footer-center p-4 bg-base-300 text-base-content mt-auto">
                <aside>
                    <p>Copyright © {{ date('Y') }} - All right reserved by {{ config('app.name', 'Sunopanel') }}</p>
                </aside>
            </footer>
        </div>

        <!-- Drawer side menu -->
        <aside class="drawer-side z-40">
            <label for="drawer-toggle" aria-label="close sidebar" class="drawer-overlay"></label>
            <div class="flex flex-col h-full">
                {{-- Sidebar Header (Optional) --}}
                <div class="h-16 flex items-center px-4 bg-base-100 border-b border-base-300 lg:hidden">
                    <span class="text-lg font-bold text-primary">{{ config('app.name', 'Sunopanel') }}</span>
                </div>
                {{-- Sidebar Menu --}}
                <ul class="menu p-4 w-64 min-h-full bg-base-100 text-base-content flex-grow">
                     {{-- Dashboard Link --}}
                    <li>
                        <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>
                            <x-icon name="home" class="h-5 w-5" />
                            Dashboard
                        </a>
                    </li>
                    {{-- Tracks Link --}}
                    <li>
                        <a href="{{ route('tracks.index') }}" @class(['active' => request()->routeIs('tracks.*')])>
                            <x-icon name="music-note" class="h-5 w-5" />
                            Tracks
                        </a>
                    </li>
                    {{-- Genres Link --}}
                    <li>
                        <a href="{{ route('genres.index') }}" @class(['active' => request()->routeIs('genres.*')])>
                             <x-icon name="tag" class="h-5 w-5" />
                            Genres
                        </a>
                    </li>
                    {{-- Playlists Link --}}
                    <li>
                        <a href="{{ route('playlists.index') }}" @class(['active' => request()->routeIs('playlists.*')])>
                            <x-icon name="collection" class="h-5 w-5" />
                            Playlists
                        </a>
                    </li>
                    {{-- Theme Toggle (Mobile Sidebar Footer) --}}
                    <li class="mt-auto lg:hidden">
                         <x-theme-toggle text="Toggle Theme" />
                    </li>
                </ul>
            </div>
        </aside>

        {{-- Notification Component --}}
        {{-- Positioned absolutely within body for better context --}}
        <div class="absolute top-5 right-5 z-50">
            <x-notification id="main-notification" />
        </div>
    </div>

    {{-- Moved flash message dispatching here --}}
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
</body>
</html>

