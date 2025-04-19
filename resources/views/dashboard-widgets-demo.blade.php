@extends('layouts.app')

@section('title', 'Dashboard Widgets Demo')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-2">Dashboard Widgets</h1>
        <p class="text-gray-600 dark:text-gray-400">A collection of reusable dashboard widgets for displaying statistics, charts, and lists.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Stats Widget - Primary -->
        <x-dashboard-widget
            title="Total Tracks"
            subtitle="All time"
            type="stats"
            variant="primary"
            value="1,247"
            change="15% increase"
            changeType="increase"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" /></svg>'
            link="/tracks"
        />

        <!-- Stats Widget - Success -->
        <x-dashboard-widget
            title="Active Users"
            subtitle="Last 30 days"
            type="stats"
            variant="success"
            value="5,283"
            change="12.4% increase"
            changeType="increase"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>'
            link="/users"
        />

        <!-- Stats Widget - Danger -->
        <x-dashboard-widget
            title="Error Rate"
            subtitle="Last 24 hours"
            type="stats"
            variant="danger"
            value="0.8%"
            change="0.3% decrease"
            changeType="decrease"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>'
            link="/logs"
        />

        <!-- Chart Widget -->
        <x-dashboard-widget
            title="Monthly Listens"
            subtitle="Last 6 months"
            type="chart"
            variant="info"
            chartId="monthly-listens-chart"
            :chartData="[
                'type' => 'line',
                'data' => [
                    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    'datasets' => [
                        [
                            'label' => 'Listens',
                            'data' => [1200, 1600, 1400, 1800, 2200, 2400],
                            'borderColor' => 'rgb(99, 102, 241)',
                            'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                            'tension' => 0.4,
                            'fill' => true
                        ]
                    ]
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false
                ]
            ]"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>'
            link="/analytics"
            bodyClass="py-2"
        />

        <!-- List Widget -->
        <x-dashboard-widget
            title="Top Genres"
            subtitle="By play count"
            type="list"
            variant="warning"
            :listItems="[
                [
                    'text' => 'Electronic',
                    'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-4 w-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3\" /></svg>',
                    'badge' => ['text' => '1,243', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'],
                    'link' => '/genres/electronic'
                ],
                [
                    'text' => 'Pop',
                    'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-4 w-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3\" /></svg>',
                    'badge' => ['text' => '982', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'],
                    'link' => '/genres/pop'
                ],
                [
                    'text' => 'Rock',
                    'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-4 w-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3\" /></svg>',
                    'badge' => ['text' => '857', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'],
                    'link' => '/genres/rock'
                ],
                [
                    'text' => 'Hip Hop',
                    'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-4 w-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3\" /></svg>',
                    'badge' => ['text' => '743', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'],
                    'link' => '/genres/hip-hop'
                ],
                [
                    'text' => 'Jazz',
                    'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-4 w-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3\" /></svg>',
                    'badge' => ['text' => '521', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'],
                    'link' => '/genres/jazz'
                ]
            ]"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>'
            link="/genres"
        />

        <!-- Custom Content Widget -->
        <x-dashboard-widget
            title="System Status"
            subtitle="All services"
            variant="primary"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>'
            link="/system-status"
        >
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="flex items-center">
                        <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                        <span class="text-sm">Web Server</span>
                    </span>
                    <span class="text-sm text-green-600 dark:text-green-400">Operational</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center">
                        <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                        <span class="text-sm">Database</span>
                    </span>
                    <span class="text-sm text-green-600 dark:text-green-400">Operational</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center">
                        <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                        <span class="text-sm">API</span>
                    </span>
                    <span class="text-sm text-green-600 dark:text-green-400">Operational</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center">
                        <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
                        <span class="text-sm">Storage</span>
                    </span>
                    <span class="text-sm text-yellow-600 dark:text-yellow-400">Degraded</span>
                </div>
            </div>
        </x-dashboard-widget>
    </div>

    <div class="mt-12">
        <h2 class="text-xl font-bold mb-4">Widget Usage Examples</h2>
        
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">Basic Stats Widget</h3>
            <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-md overflow-x-auto text-sm mb-4"><code>&lt;x-dashboard-widget
    title="Total Tracks"
    subtitle="All time"
    type="stats"
    variant="primary"
    value="1,247"
    change="15% increase"
    changeType="increase"
    icon='&lt;svg class="h-5 w-5" ... &gt;&lt;/svg&gt;'
    link="/tracks"
/&gt;</code></pre>
            
            <h3 class="text-lg font-semibold mb-4 mt-8">Chart Widget</h3>
            <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-md overflow-x-auto text-sm mb-4"><code>&lt;x-dashboard-widget
    title="Monthly Listens"
    subtitle="Last 6 months"
    type="chart"
    variant="info"
    chartId="monthly-listens-chart"
    :chartData="[
        'type' => 'line',
        'data' => [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'Listens',
                    'data' => [1200, 1600, 1400, 1800, 2200, 2400],
                    'borderColor' => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'tension' => 0.4,
                    'fill' => true
                ]
            ]
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false
        ]
    ]"
    icon='&lt;svg class="h-5 w-5" ... &gt;&lt;/svg&gt;'
    link="/analytics"
/&gt;</code></pre>
            
            <h3 class="text-lg font-semibold mb-4 mt-8">List Widget</h3>
            <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-md overflow-x-auto text-sm mb-4"><code>&lt;x-dashboard-widget
    title="Top Genres"
    subtitle="By play count"
    type="list"
    variant="warning"
    :listItems="[
        [
            'text' => 'Electronic',
            'icon' => '&lt;svg class="h-4 w-4" ... &gt;&lt;/svg&gt;',
            'badge' => ['text' => '1,243', 'class' => 'bg-yellow-100 text-yellow-800'],
            'link' => '/genres/electronic'
        ],
        // More items...
    ]"
    icon='&lt;svg class="h-5 w-5" ... &gt;&lt;/svg&gt;'
    link="/genres"
/&gt;</code></pre>

            <h3 class="text-lg font-semibold mb-4 mt-8">Custom Content Widget</h3>
            <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-md overflow-x-auto text-sm"><code>&lt;x-dashboard-widget
    title="System Status"
    subtitle="All services"
    variant="primary"
    icon='&lt;svg class="h-5 w-5" ... &gt;&lt;/svg&gt;'
    link="/system-status"
&gt;
    &lt;!-- Custom content here --&gt;
    &lt;div class="space-y-3"&gt;
        &lt;div class="flex items-center justify-between"&gt;
            &lt;span class="flex items-center"&gt;
                &lt;span class="w-3 h-3 bg-green-500 rounded-full mr-2"&gt;&lt;/span&gt;
                &lt;span class="text-sm"&gt;Web Server&lt;/span&gt;
            &lt;/span&gt;
            &lt;span class="text-sm text-green-600"&gt;Operational&lt;/span&gt;
        &lt;/div&gt;
        &lt;!-- More status items --&gt;
    &lt;/div&gt;
&lt;/x-dashboard-widget&gt;</code></pre>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
@endsection 