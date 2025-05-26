@extends('layouts.app')

@section('title', 'Import Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Import Dashboard</h1>
        <p class="text-gray-600">Comprehensive import system for music tracks from various sources with real-time monitoring</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" id="stats-cards">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Tracks</p>
                    <p class="text-2xl font-bold text-gray-900" id="total-tracks">{{ number_format($stats['total_tracks']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Completed</p>
                    <p class="text-2xl font-bold text-gray-900" id="completed-tracks">{{ number_format($stats['completed_tracks']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Processing</p>
                    <p class="text-2xl font-bold text-gray-900" id="processing-tracks">{{ number_format($stats['processing_tracks']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Queue Jobs</p>
                    <p class="text-2xl font-bold text-gray-900" id="pending-jobs">{{ number_format($stats['pending_jobs']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Genres</p>
                    <p class="text-2xl font-bold text-gray-900" id="total-genres">{{ number_format($stats['total_genres']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending Tracks</p>
                    <p class="text-2xl font-bold text-gray-900" id="pending-tracks">{{ number_format($stats['pending_tracks']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Failed Tracks</p>
                    <p class="text-2xl font-bold text-gray-900" id="failed-tracks">{{ number_format($stats['failed_tracks']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Tabs -->
    <div class="bg-white rounded-lg shadow-md mb-8">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button class="import-tab active border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="json">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        JSON Import
                    </div>
                </button>
                <button class="import-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="discover">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Suno Discover
                    </div>
                </button>
                <button class="import-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="search">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Suno Search
                    </div>
                </button>
                <button class="import-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="unified">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        Unified Import
                    </div>
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- JSON Import Tab -->
            <div id="json-tab" class="tab-content">
                <div class="mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">JSON Import</h3>
                    <p class="text-gray-600 mb-4">Import music tracks from JSON files or remote URLs. Supports multiple formats including pipe-delimited, JSON objects, and array formats.</p>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <h4 class="font-medium text-blue-900 mb-2">Supported Formats:</h4>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li><strong>Pipe Delimited:</strong> filename|audio_url|image_url|genres</li>
                            <li><strong>JSON Objects:</strong> Array of objects with title, audio_url, image_url, tags</li>
                            <li><strong>Array Format:</strong> Simple arrays with track data</li>
                            <li><strong>Auto Detect:</strong> Automatically detects the format</li>
                        </ul>
                    </div>
                </div>
                
                <form id="json-import-form" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Source Type</label>
                            <select name="source_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="url">Remote URL</option>
                                <option value="file">Upload File</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Choose between uploading a local file or fetching from a remote URL</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data Format</label>
                            <select name="format" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="auto">Auto Detect</option>
                                <option value="pipe">Pipe Delimited (|)</option>
                                <option value="object">JSON Objects</option>
                                <option value="array">Array Format</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Format of the JSON data structure</p>
                        </div>
                    </div>

                    <div id="url-input" class="source-input">
                        <label class="block text-sm font-medium text-gray-700 mb-2">JSON URL</label>
                        <input type="url" name="json_url" placeholder="https://example.com/tracks.json" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">URL to fetch JSON data from (must be publicly accessible)</p>
                    </div>

                    <div id="file-input" class="source-input hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">JSON File</label>
                        <input type="file" name="json_file" accept=".json,.txt" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Upload a local JSON file (max 10MB)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">JSON Field (for nested data)</label>
                        <input type="text" name="field" value="data" placeholder="data" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Field name containing track data (leave as 'data' for root level)</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Limit Tracks</label>
                            <input type="number" name="limit" min="1" max="10000" placeholder="No limit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Maximum number of tracks to import (0 = no limit)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Skip First</label>
                            <input type="number" name="skip" min="0" placeholder="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Number of tracks to skip from the beginning</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="dry_run" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Dry Run</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="process" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Auto Process</span>
                        </label>
                    </div>
                    <div class="text-xs text-gray-500">
                        <p><strong>Dry Run:</strong> Preview what would be imported without creating tracks</p>
                        <p><strong>Auto Process:</strong> Automatically start downloading and processing imported tracks</p>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 font-medium">
                        Start JSON Import
                    </button>
                </form>
            </div>

            <!-- Suno Discover Tab -->
            <div id="discover-tab" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Suno Discover Import</h3>
                    <p class="text-gray-600 mb-4">Import trending and popular tracks from Suno's discover API. Access curated collections of high-quality music tracks.</p>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <h4 class="font-medium text-green-900 mb-2">Available Sections:</h4>
                        <ul class="text-sm text-green-800 space-y-1">
                            <li><strong>Trending Songs:</strong> Currently popular tracks with high engagement</li>
                            <li><strong>New Songs:</strong> Recently published tracks</li>
                            <li><strong>Popular Songs:</strong> All-time popular tracks</li>
                        </ul>
                    </div>
                </div>
                
                <form id="discover-import-form" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                        <select name="section" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="trending_songs">Trending Songs</option>
                            <option value="new_songs">New Songs</option>
                            <option value="popular_songs">Popular Songs</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Choose which section of Suno's discover feed to import from</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Page Size</label>
                            <input type="number" name="page_size" value="20" min="1" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <p class="text-xs text-gray-500 mt-1">Number of tracks per page (1-100)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pages</label>
                            <input type="number" name="pages" value="1" min="1" max="20" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <p class="text-xs text-gray-500 mt-1">Number of pages to fetch (1-20)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Index</label>
                            <input type="number" name="start_index" min="0" placeholder="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <p class="text-xs text-gray-500 mt-1">Starting index for pagination</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="dry_run" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <span class="ml-2 text-sm text-gray-700">Dry Run</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="process" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <span class="ml-2 text-sm text-gray-700">Auto Process</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-200 font-medium">
                        Start Discover Import
                    </button>
                </form>
            </div>

            <!-- Suno Search Tab -->
            <div id="search-tab" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Suno Search Import</h3>
                    <p class="text-gray-600 mb-4">Search and import specific tracks from Suno's public library. Use advanced filtering and ranking options.</p>
                    
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-4">
                        <h4 class="font-medium text-purple-900 mb-2">Ranking Options:</h4>
                        <ul class="text-sm text-purple-800 space-y-1">
                            <li><strong>Trending:</strong> Currently trending tracks</li>
                            <li><strong>Most Recent:</strong> Newest tracks first</li>
                            <li><strong>Most Relevant:</strong> Best match for search term</li>
                            <li><strong>Upvote Count:</strong> Highest rated tracks</li>
                            <li><strong>Play Count:</strong> Most played tracks</li>
                        </ul>
                    </div>
                </div>
                
                <form id="search-import-form" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Term</label>
                        <input type="text" name="term" placeholder="Enter search term (leave empty for all public songs)" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">Search for specific genres, artists, or keywords. Leave empty to get all public songs.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rank By</label>
                        <select name="rank_by" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="most_relevant">Most Relevant</option>
                            <option value="trending">Trending</option>
                            <option value="most_recent">Most Recent</option>
                            <option value="upvote_count">Upvote Count</option>
                            <option value="play_count">Play Count</option>
                            <option value="dislike_count">Dislike Count</option>
                            <option value="by_hour">By Hour</option>
                            <option value="by_day">By Day</option>
                            <option value="by_week">By Week</option>
                            <option value="by_month">By Month</option>
                            <option value="all_time">All Time</option>
                            <option value="default">Default</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">How to rank and sort the search results</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Size per Page</label>
                            <input type="number" name="size" value="20" min="1" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <p class="text-xs text-gray-500 mt-1">Number of tracks per page (1-100)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pages</label>
                            <input type="number" name="pages" value="1" min="1" max="20" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <p class="text-xs text-gray-500 mt-1">Number of pages to fetch (1-20)</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="instrumental" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">Instrumental Only</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="dry_run" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">Dry Run</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="process" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">Auto Process</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-purple-600 text-white py-3 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition duration-200 font-medium">
                        Start Search Import
                    </button>
                </form>
            </div>

            <!-- Unified Import Tab -->
            <div id="unified-tab" class="tab-content hidden">
                <div class="mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Unified Import</h3>
                    <p class="text-gray-600 mb-4">Import from multiple sources simultaneously. Combine JSON, Suno Discover, and Suno Search in a single operation.</p>
                    
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-4">
                        <h4 class="font-medium text-indigo-900 mb-2">Multi-Source Import:</h4>
                        <ul class="text-sm text-indigo-800 space-y-1">
                            <li><strong>Efficient:</strong> Import from multiple sources in one command</li>
                            <li><strong>Coordinated:</strong> Unified progress tracking and error handling</li>
                            <li><strong>Flexible:</strong> Choose any combination of sources</li>
                        </ul>
                    </div>
                </div>
                
                <form id="unified-import-form" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Import Sources</label>
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="checkbox" name="sources[]" value="discover" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Suno Discover API</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="sources[]" value="search" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Suno Search API</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="sources[]" value="json" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">JSON Source</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Select which sources to import from</p>
                    </div>

                    <!-- JSON Source Options -->
                    <div id="unified-json-options" class="border border-gray-200 rounded-lg p-4 hidden">
                        <h4 class="font-medium text-gray-900 mb-3">JSON Source Options</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">JSON File Path</label>
                                <input type="text" name="json_file" placeholder="/path/to/file.json" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">JSON URL</label>
                                <input type="url" name="json_url" placeholder="https://example.com/tracks.json" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- Discover Options -->
                    <div id="unified-discover-options" class="border border-gray-200 rounded-lg p-4 hidden">
                        <h4 class="font-medium text-gray-900 mb-3">Discover Options</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pages</label>
                                <input type="number" name="discover_pages" value="1" min="1" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Page Size</label>
                                <input type="number" name="discover_size" value="20" min="1" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- Search Options -->
                    <div id="unified-search-options" class="border border-gray-200 rounded-lg p-4 hidden">
                        <h4 class="font-medium text-gray-900 mb-3">Search Options</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search Term</label>
                                <input type="text" name="search_term" placeholder="Optional search term" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rank By</label>
                                <select name="search_rank" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="most_relevant">Most Relevant</option>
                                    <option value="trending">Trending</option>
                                    <option value="most_recent">Most Recent</option>
                                    <option value="upvote_count">Upvote Count</option>
                                    <option value="play_count">Play Count</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pages</label>
                                <input type="number" name="search_pages" value="1" min="1" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Page Size</label>
                                <input type="number" name="search_size" value="20" min="1" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="dry_run" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Dry Run</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="process" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Auto Process</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white py-3 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-200 font-medium">
                        Start Unified Import
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Real-time Progress Section -->
    <div id="progress-section" class="hidden">
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Import Progress</h3>
                <button id="stop-import" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-200 text-sm">
                    Stop Import
                </button>
            </div>
            
            <div id="progress-content">
                <!-- Progress bars and status will be inserted here -->
            </div>
        </div>
    </div>

    <!-- Queue Status -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">System Status</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-600">Queue Jobs</p>
                    <span class="text-sm text-gray-500" id="queue-status">Active</span>
                </div>
                <div class="flex items-center">
                    <div class="flex-1 bg-gray-200 rounded-full h-2 mr-3">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: 0%" id="queue-progress"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900" id="queue-count">{{ number_format($stats['pending_jobs']) }}</span>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-600">Failed Jobs</p>
                    <a href="{{ route('queue.index') }}" class="text-sm text-blue-600 hover:text-blue-800">View Details</a>
                </div>
                <div class="flex items-center">
                    <div class="flex-1 bg-gray-200 rounded-full h-2 mr-3">
                        <div class="bg-red-600 h-2 rounded-full" style="width: {{ $stats['failed_jobs'] > 0 ? '100' : '0' }}%" id="failed-progress"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900" id="failed-count">{{ number_format($stats['failed_jobs']) }}</span>
                </div>
            </div>
        </div>
        
        <div class="mt-6 flex space-x-4">
            <a href="{{ route('queue.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Queue Dashboard
            </a>
            <button id="refresh-stats" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh Stats
            </button>
        </div>
    </div>
</div>

@push('scripts')
@vite('resources/js/import-dashboard.js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    new ImportDashboard();
});
</script>
@endpush
@endsection 