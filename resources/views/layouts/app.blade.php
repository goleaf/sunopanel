<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="SunoPanel - Music Management System">
    <meta name="theme-color" content="#4f46e5">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="renderer" content="webkit">

    <title>{{ config('app.name', 'Sunopanel') }}</title>

    <!-- Preload Critical Assets -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Preload Critical CSS -->
    @vite(['resources/css/app.css'])
    
    <!-- Scripts - Defer loading -->
    @vite(['resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="font-sans antialiased min-h-screen bg-gray-50">
    <!-- Server-side rendered content -->
    <div class="min-h-screen flex flex-col">
        <!-- Top Navigation Bar -->
        <header class="bg-white shadow-sm py-2">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <!-- Logo and Brand -->
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center">
                        <svg class="w-8 h-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <span class="ml-3 text-xl font-bold text-indigo-600">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    
                    <!-- Desktop Navigation -->
                    <nav class="hidden md:flex items-center space-x-1">
                        <a href="{{ route('dashboard') }}" wire:navigate.prefetch class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }} flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </a>
                        <a href="{{ route('tracks.index') }}" wire:navigate.prefetch class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('tracks.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }} flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                            </svg>
                            Tracks
                        </a>
                        <a href="{{ route('genres.index') }}" wire:navigate.prefetch class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('genres.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }} flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            Genres
                        </a>
                        <a href="{{ route('playlists.index') }}" wire:navigate.prefetch class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('playlists.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }} flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Playlists
                        </a>
                        <a href="{{ route('system.stats') }}" wire:navigate.prefetch class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('system.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }} flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            System Stats
                        </a>
                    </nav>
                    
                    <!-- Mobile menu button -->
                    <div class="flex items-center md:hidden">
                        <div x-data="{ open: false }" class="relative">
                            <button 
                                x-on:click="open = !open" 
                                class="bg-white rounded-md p-2 inline-flex items-center justify-center text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" 
                                aria-expanded="false"
                            >
                                <span class="sr-only">Open main menu</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            
                            <div 
                                x-show="open" 
                                x-on:click.away="open = false" 
                                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50" 
                                style="display: none;"
                            >
                                <a href="{{ route('dashboard') }}" wire:navigate.prefetch class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-gray-100 font-medium' : '' }}">Dashboard</a>
                                <a href="{{ route('tracks.index') }}" wire:navigate.prefetch class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('tracks.*') ? 'bg-gray-100 font-medium' : '' }}">Tracks</a>
                                <a href="{{ route('genres.index') }}" wire:navigate.prefetch class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('genres.*') ? 'bg-gray-100 font-medium' : '' }}">Genres</a>
                                <a href="{{ route('playlists.index') }}" wire:navigate.prefetch class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('playlists.*') ? 'bg-gray-100 font-medium' : '' }}">Playlists</a>
                                <a href="{{ route('system.stats') }}" wire:navigate.prefetch class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('system.*') ? 'bg-gray-100 font-medium' : '' }}">System Stats</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-grow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <p class="text-center text-sm text-gray-500">
                    Copyright © {{ date('Y') }} - All rights reserved by {{ config('app.name', 'Sunopanel') }}
                </p>
            </div>
        </footer>

        {{-- Notification Component --}}
        <div class="fixed top-5 right-5 z-50">
            <x-notification id="main-notification" />
        </div>
    </div>

    <!-- Livewire Scripts -->
    @livewireScriptConfig(['nonce' => csp_nonce()])
    
    <!-- CSS for Livewire Navigation indicator -->
    <style>
        body.navigating:after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #4f46e5;
            animation: progressBar 2s linear infinite;
            z-index: 9999;
        }
        
        @keyframes progressBar {
            0% { width: 0; }
            50% { width: 50%; }
            100% { width: 100%; }
        }
    </style>
    
    <!-- Flash message handling -->
    <script>
        document.addEventListener('livewire:init', () => {
            @if (session('success'))
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: "{{ session('success') }}",
                        type: 'success'
                    }
                }));
            @endif
            @if (session('error'))
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: "{{ session('error') }}",
                        type: 'error'
                    }
                }));
            @endif
            @if (session('info'))
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: "{{ session('info') }}",
                        type: 'info'
                    }
                }));
            @endif
            @if (session('warning'))
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: "{{ session('warning') }}",
                        type: 'warning'
                    }
                }));
            @endif
        });
    </script>
</body>
</html>

