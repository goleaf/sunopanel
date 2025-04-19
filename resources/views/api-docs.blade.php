<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SunoPanel API Documentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-10">
            <h1 class="text-3xl font-bold mb-4">SunoPanel API Documentation</h1>
            <p class="text-lg text-gray-600 dark:text-gray-400">API Reference for the SunoPanel Music Platform</p>
        </header>

        <div class="flex flex-col md:flex-row gap-8">
            <!-- Sidebar Navigation -->
            <aside class="w-full md:w-64 shrink-0">
                <div class="sticky top-8 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h2 class="text-xl font-semibold mb-4">API Endpoints</h2>
                    <nav>
                        <ul class="space-y-2">
                            <li>
                                <a href="#authentication" class="block text-blue-600 dark:text-blue-400 hover:underline">Authentication</a>
                            </li>
                            <li>
                                <a href="#tracks" class="block text-blue-600 dark:text-blue-400 hover:underline">Tracks</a>
                                <ul class="ml-4 mt-1 space-y-1">
                                    <li><a href="#get-tracks" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">List Tracks</a></li>
                                    <li><a href="#get-track" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">Get Track</a></li>
                                    <li><a href="#create-track" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">Create Track</a></li>
                                    <li><a href="#update-track" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">Update Track</a></li>
                                    <li><a href="#delete-track" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">Delete Track</a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="#playlists" class="block text-blue-600 dark:text-blue-400 hover:underline">Playlists</a>
                                <ul class="ml-4 mt-1 space-y-1">
                                    <li><a href="#get-playlists" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">List Playlists</a></li>
                                    <li><a href="#get-playlist" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">Get Playlist</a></li>
                                    <li><a href="#create-playlist" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">Create Playlist</a></li>
                                    <li><a href="#add-tracks-to-playlist" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">Add Tracks</a></li>
                                    <li><a href="#update-playlist" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">Update Playlist</a></li>
                                    <li><a href="#delete-playlist" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">Delete Playlist</a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="#genres" class="block text-blue-600 dark:text-blue-400 hover:underline">Genres</a>
                                <ul class="ml-4 mt-1 space-y-1">
                                    <li><a href="#get-genres" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">List Genres</a></li>
                                    <li><a href="#tracks-by-genre" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">Tracks by Genre</a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="#users" class="block text-blue-600 dark:text-blue-400 hover:underline">Users</a>
                                <ul class="ml-4 mt-1 space-y-1">
                                    <li><a href="#get-user" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">User Profile</a></li>
                                    <li><a href="#update-user" class="block text-blue-600 dark:text-blue-400 hover:underline text-sm">Update Profile</a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="#errors" class="block text-blue-600 dark:text-blue-400 hover:underline">Error Handling</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1">
                <!-- Authentication -->
                <section id="authentication" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">Authentication</h2>
                    <p class="mb-4">
                        SunoPanel API uses Laravel Sanctum for API authentication. All authenticated endpoints require a Bearer token in the Authorization header.
                    </p>

                    <!-- Login -->
                    <div class="mb-6">
                        <div class="flex items-center bg-blue-50 dark:bg-blue-900/20 p-2 rounded-t-md">
                            <span class="px-2 py-1 bg-blue-600 text-white text-xs font-bold rounded mr-2">POST</span>
                            <span class="font-mono text-sm">/api/login</span>
                        </div>
                        <div class="border dark:border-gray-700 p-4 rounded-b-md">
                            <h4 class="font-semibold mb-2">Request Body</h4>
                            <pre><code class="language-json">{
  "email": "user@example.com",
  "password": "password"
}</code></pre>
                            <h4 class="font-semibold mt-4 mb-2">Response</h4>
                            <pre><code class="language-json">{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "created_at": "2023-01-01T00:00:00.000000Z"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz"
}</code></pre>
                        </div>
                    </div>

                    <!-- Logout -->
                    <div class="mb-6">
                        <div class="flex items-center bg-blue-50 dark:bg-blue-900/20 p-2 rounded-t-md">
                            <span class="px-2 py-1 bg-blue-600 text-white text-xs font-bold rounded mr-2">POST</span>
                            <span class="font-mono text-sm">/api/logout</span>
                        </div>
                        <div class="border dark:border-gray-700 p-4 rounded-b-md">
                            <h4 class="font-semibold mb-2">Headers</h4>
                            <pre><code class="language-json">{
  "Authorization": "Bearer {token}"
}</code></pre>
                            <h4 class="font-semibold mt-4 mb-2">Response</h4>
                            <pre><code class="language-json">{
  "message": "Logged out successfully"
}</code></pre>
                        </div>
                    </div>
                </section>

                <!-- Tracks -->
                <section id="tracks" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">Tracks</h2>
                    
                    <!-- Get Tracks -->
                    <div id="get-tracks" class="mb-6">
                        <div class="flex items-center bg-green-50 dark:bg-green-900/20 p-2 rounded-t-md">
                            <span class="px-2 py-1 bg-green-600 text-white text-xs font-bold rounded mr-2">GET</span>
                            <span class="font-mono text-sm">/api/tracks</span>
                        </div>
                        <div class="border dark:border-gray-700 p-4 rounded-b-md">
                            <h4 class="font-semibold mb-2">Query Parameters</h4>
                            <table class="min-w-full text-sm mb-4">
                                <thead>
                                    <tr class="border-b dark:border-gray-700">
                                        <th class="text-left py-2">Parameter</th>
                                        <th class="text-left py-2">Type</th>
                                        <th class="text-left py-2">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b dark:border-gray-700">
                                        <td class="py-2">page</td>
                                        <td class="py-2">integer</td>
                                        <td class="py-2">Page number</td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700">
                                        <td class="py-2">per_page</td>
                                        <td class="py-2">integer</td>
                                        <td class="py-2">Results per page (default: 15)</td>
                                    </tr>
                                    <tr class="border-b dark:border-gray-700">
                                        <td class="py-2">search</td>
                                        <td class="py-2">string</td>
                                        <td class="py-2">Search term</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2">genre_id</td>
                                        <td class="py-2">integer</td>
                                        <td class="py-2">Filter by genre ID</td>
                                    </tr>
                                </tbody>
                            </table>
                            <h4 class="font-semibold mt-4 mb-2">Response</h4>
                            <pre><code class="language-json">{
  "data": [
    {
      "id": 1,
      "title": "Summer Vibes",
      "artist": "DJ Cool",
      "duration": 180,
      "file_path": "/storage/tracks/summer-vibes.mp3",
      "cover_image": "/storage/covers/summer-vibes.jpg",
      "genres": [
        {
          "id": 2,
          "name": "Electronic"
        }
      ],
      "created_at": "2023-01-01T00:00:00.000000Z"
    },
    // ... more tracks
  ],
  "links": {
    "first": "http://sunopanel.example.com/api/tracks?page=1",
    "last": "http://sunopanel.example.com/api/tracks?page=5",
    "prev": null,
    "next": "http://sunopanel.example.com/api/tracks?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "http://sunopanel.example.com/api/tracks",
    "per_page": 15,
    "to": 15,
    "total": 75
  }
}</code></pre>
                        </div>
                    </div>

                    <!-- Get Track -->
                    <div id="get-track" class="mb-6">
                        <div class="flex items-center bg-green-50 dark:bg-green-900/20 p-2 rounded-t-md">
                            <span class="px-2 py-1 bg-green-600 text-white text-xs font-bold rounded mr-2">GET</span>
                            <span class="font-mono text-sm">/api/tracks/{id}</span>
                        </div>
                        <div class="border dark:border-gray-700 p-4 rounded-b-md">
                            <h4 class="font-semibold mb-2">Path Parameters</h4>
                            <table class="min-w-full text-sm mb-4">
                                <thead>
                                    <tr class="border-b dark:border-gray-700">
                                        <th class="text-left py-2">Parameter</th>
                                        <th class="text-left py-2">Type</th>
                                        <th class="text-left py-2">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="py-2">id</td>
                                        <td class="py-2">integer</td>
                                        <td class="py-2">Track ID</td>
                                    </tr>
                                </tbody>
                            </table>
                            <h4 class="font-semibold mt-4 mb-2">Response</h4>
                            <pre><code class="language-json">{
  "data": {
    "id": 1,
    "title": "Summer Vibes",
    "artist": "DJ Cool",
    "duration": 180,
    "file_path": "/storage/tracks/summer-vibes.mp3",
    "cover_image": "/storage/covers/summer-vibes.jpg",
    "genres": [
      {
        "id": 2,
        "name": "Electronic"
      }
    ],
    "created_at": "2023-01-01T00:00:00.000000Z",
    "updated_at": "2023-01-01T00:00:00.000000Z"
  }
}</code></pre>
                        </div>
                    </div>

                    <!-- And so on for all other endpoints... -->
                    <!-- Continue with Create Track, Update Track, Delete Track sections -->
                    <div id="create-track" class="mb-6">
                        <div class="flex items-center bg-blue-50 dark:bg-blue-900/20 p-2 rounded-t-md">
                            <span class="px-2 py-1 bg-blue-600 text-white text-xs font-bold rounded mr-2">POST</span>
                            <span class="font-mono text-sm">/api/tracks</span>
                        </div>
                        <div class="border dark:border-gray-700 p-4 rounded-b-md">
                            <p class="mb-4">Creates a new track. Requires authentication.</p>
                            <h4 class="font-semibold mb-2">Request Body</h4>
                            <pre><code class="language-json">{
  "title": "New Track Title",
  "artist": "Artist Name",
  "file": [Binary File Data],
  "cover_image": [Binary Image Data],
  "genre_ids": [1, 3]
}</code></pre>
                            <h4 class="font-semibold mt-4 mb-2">Response</h4>
                            <pre><code class="language-json">{
  "data": {
    "id": 76,
    "title": "New Track Title",
    "artist": "Artist Name",
    "duration": 195,
    "file_path": "/storage/tracks/new-track-title.mp3",
    "cover_image": "/storage/covers/new-track-title.jpg",
    "genres": [
      {
        "id": 1,
        "name": "Pop"
      },
      {
        "id": 3,
        "name": "Rock"
      }
    ],
    "created_at": "2023-06-15T10:30:00.000000Z",
    "updated_at": "2023-06-15T10:30:00.000000Z"
  }
}</code></pre>
                        </div>
                    </div>
                </section>

                <!-- Playlists, Genres, Users sections would follow a similar pattern -->
                
                <!-- Error Handling -->
                <section id="errors" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">Error Handling</h2>
                    <p class="mb-4">
                        The API uses standard HTTP status codes to indicate the success or failure of a request. 
                        Error responses include a message explaining what went wrong.
                    </p>

                    <h3 class="text-xl font-semibold mt-6 mb-2">Common Error Codes</h3>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="text-left py-2">Status Code</th>
                                <th class="text-left py-2">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2">400 Bad Request</td>
                                <td class="py-2">Invalid request parameters</td>
                            </tr>
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2">401 Unauthorized</td>
                                <td class="py-2">Authentication required or failed</td>
                            </tr>
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2">403 Forbidden</td>
                                <td class="py-2">Permission denied</td>
                            </tr>
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2">404 Not Found</td>
                                <td class="py-2">Resource not found</td>
                            </tr>
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2">422 Unprocessable Entity</td>
                                <td class="py-2">Validation errors</td>
                            </tr>
                            <tr>
                                <td class="py-2">500 Internal Server Error</td>
                                <td class="py-2">Server-side error</td>
                            </tr>
                        </tbody>
                    </table>

                    <h3 class="text-xl font-semibold mt-6 mb-2">Error Response Example</h3>
                    <pre><code class="language-json">{
  "message": "The given data was invalid.",
  "errors": {
    "title": [
      "The title field is required."
    ],
    "artist": [
      "The artist field is required."
    ],
    "file": [
      "The file must be an mp3 file."
    ]
  }
}</code></pre>
                </section>
            </main>
        </div>
    </div>
</body>
</html> 