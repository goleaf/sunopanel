<?php

return [
    'brand' => [
        'name' => 'SunoPanel',
        'route' => 'home.index',
        'icon' => 'music-note',
    ],

    'main_actions' => [
        [
            'label' => 'Add Tracks',
            'route' => 'home.index',
            'icon' => 'plus',
            'active_routes' => ['home*'],
        ],
        [
            'label' => 'Songs',
            'route' => 'tracks.index',
            'icon' => 'music-note',
            'active_routes' => ['tracks*'],
        ],
        [
            'label' => 'Genres',
            'route' => 'genres.index',
            'icon' => 'tag',
            'active_routes' => ['genres*'],
        ],
    ],

    'import_upload' => [
        [
            'label' => 'Import',
            'route' => 'import.index',
            'icon' => 'download',
            'active_routes' => ['import.*'],
            'mobile_label' => 'Import Dashboard',
        ],
        [
            'label' => 'Upload',
            'route' => 'videos.upload',
            'icon' => 'upload',
            'active_routes' => ['videos*'],
            'mobile_label' => 'Upload Video',
        ],
        [
            'label' => 'Monitor',
            'route' => 'monitoring.index',
            'icon' => 'chart-bar',
            'active_routes' => ['monitoring.*'],
            'mobile_label' => 'System Monitor',
        ],
    ],

    'youtube' => [
        'label' => 'YouTube',
        'icon' => 'youtube',
        'active_routes' => ['youtube.*', 'queue.*'],
        'items' => [
            [
                'label' => 'Status & Upload',
                'route' => 'youtube.status',
                'icon' => 'check-circle',
                'active_routes' => ['youtube.status'],
            ],
            [
                'label' => 'Analytics',
                'route' => 'youtube.analytics.index',
                'icon' => 'chart-bar',
                'active_routes' => ['youtube.analytics.*'],
            ],
            [
                'label' => 'Bulk Upload',
                'route' => 'youtube.bulk.index',
                'icon' => 'upload',
                'active_routes' => ['youtube.bulk.*'],
            ],
            [
                'label' => 'Queue Monitor',
                'route' => 'queue.index',
                'icon' => 'clock',
                'active_routes' => ['queue.*'],
                'separator' => true,
            ],
        ],
    ],

    'system' => [
        [
            'label' => 'Settings',
            'route' => 'settings.index',
            'icon' => 'cog',
            'active_routes' => ['settings*'],
        ],
    ],

    'icons' => [
        'plus' => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
        'music-note' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3',
        'tag' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        'download' => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10',
        'upload' => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12',
        'chart-bar' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'youtube' => 'M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z',
        'youtube-play' => '9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02',
        'check-circle' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'cog' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'cog-inner' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'chevron-down' => 'M19 9l-7 7-7-7',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'x' => 'M6 18L18 6M6 6l12 12',
    ],
]; 