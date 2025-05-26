@extends('layouts.app')

@section('title', 'System Monitoring Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Header Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-blue-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">System Monitoring</h1>
                    <p class="text-gray-600">Real-time system health, performance metrics, and error tracking</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Auto Refresh</label>
                    <input type="checkbox" id="auto-refresh-toggle" checked class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                </div>
                <select id="refresh-rate" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="10">10s</option>
                    <option value="30" selected>30s</option>
                    <option value="60">1m</option>
                    <option value="300">5m</option>
                </select>
                <button onclick="window.monitoringDashboard?.refreshData()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- System Health Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Database Health -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7c0-2.21-1.79-4-4-4H8c-2.21 0-4 1.79-4 4z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Database</h3>
                        <p class="text-sm text-gray-600">Connection Status</p>
                    </div>
                </div>
                <div id="database-indicator" class="w-3 h-3 rounded-full {{ $systemHealth['database']['connected'] ? 'bg-green-500' : 'bg-red-500' }}"></div>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status</span>
                    <span class="font-medium {{ $systemHealth['database']['connected'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $systemHealth['database']['connected'] ? 'Connected' : 'Disconnected' }}
                    </span>
                </div>
                @if($systemHealth['database']['connected'])
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Response Time</span>
                    <span class="font-medium text-gray-900">{{ $systemHealth['database']['response_time_ms'] }}ms</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Redis Health -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Redis Cache</h3>
                        <p class="text-sm text-gray-600">Cache Status</p>
                    </div>
                </div>
                <div id="redis-indicator" class="w-3 h-3 rounded-full {{ $systemHealth['redis']['connected'] ? 'bg-green-500' : 'bg-red-500' }}"></div>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status</span>
                    <span class="font-medium {{ $systemHealth['redis']['connected'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $systemHealth['redis']['connected'] ? 'Connected' : 'Disconnected' }}
                    </span>
                </div>
                @if($systemHealth['redis']['connected'])
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Response Time</span>
                    <span class="font-medium text-gray-900">{{ $systemHealth['redis']['response_time_ms'] }}ms</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Queue Health -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Queue Jobs</h3>
                        <p class="text-sm text-gray-600">Processing Status</p>
                    </div>
                </div>
                <div id="queue-indicator" class="w-3 h-3 rounded-full {{ $systemHealth['queue']['pending_jobs'] < 100 ? 'bg-green-500' : 'bg-yellow-500' }}"></div>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Pending</span>
                    <span id="queue-pending" class="font-medium text-purple-600">{{ number_format($systemHealth['queue']['pending_jobs']) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Failed</span>
                    <span id="queue-failed" class="font-medium text-red-600">{{ number_format($systemHealth['queue']['failed_jobs']) }}</span>
                </div>
            </div>
        </div>

        <!-- Storage Health -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 011 1v1a1 1 0 01-1 1h-1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7H3a1 1 0 01-1-1V5a1 1 0 011-1h4z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Disk Storage</h3>
                        <p class="text-sm text-gray-600">Usage Status</p>
                    </div>
                </div>
                <div class="w-3 h-3 rounded-full {{ $systemHealth['storage']['disk_usage_percent'] < 80 ? 'bg-green-500' : ($systemHealth['storage']['disk_usage_percent'] < 90 ? 'bg-yellow-500' : 'bg-red-500') }}"></div>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Usage</span>
                    <span class="font-medium text-green-600">{{ $systemHealth['storage']['disk_usage_percent'] }}%</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Free Space</span>
                    <span class="font-medium text-gray-900">{{ number_format($systemHealth['storage']['disk_free_space'] / 1024 / 1024 / 1024, 1) }}GB</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Memory Usage -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Memory Usage</h3>
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                    <span class="text-sm text-gray-600">Current</span>
                    <div class="w-2 h-2 bg-orange-500 rounded-full ml-4"></div>
                    <span class="text-sm text-gray-600">Peak</span>
                </div>
            </div>
            <div class="space-y-6">
                <div>
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Current Usage</span>
                        <span id="memory-current">{{ number_format($performanceMetrics['memory_usage']['current'] / 1024 / 1024, 1) }}MB</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div id="memory-usage-bar" class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300" style="width: {{ min(($performanceMetrics['memory_usage']['current'] / $performanceMetrics['memory_usage']['peak']) * 100, 100) }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Peak Usage</span>
                        <span id="memory-peak">{{ number_format($performanceMetrics['memory_usage']['peak'] / 1024 / 1024, 1) }}MB</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-gradient-to-r from-orange-500 to-orange-600 h-3 rounded-full" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Stats -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Real-time Statistics</h3>
                <div class="text-xs text-gray-500" id="last-refresh">
                    Last updated: <span id="last-refresh-time">{{ now()->format('H:i:s') }}</span>
                </div>
            </div>
            <div id="realtime-stats" class="space-y-4">
                <!-- Real-time stats will be loaded here -->
                <div class="animate-pulse space-y-3">
                    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                    <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Metrics and System Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Error Metrics -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Error Metrics</h3>
                <div class="w-3 h-3 rounded-full {{ $errorMetrics['error_rate'] < 0.05 ? 'bg-green-500' : ($errorMetrics['error_rate'] < 0.1 ? 'bg-yellow-500' : 'bg-red-500') }}"></div>
            </div>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-700">Error Rate</span>
                    <span class="text-lg font-bold text-red-600">{{ number_format($errorMetrics['error_rate'] * 100, 2) }}%</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-700">Recent Errors</span>
                    <span class="text-lg font-bold text-orange-600">{{ count($errorMetrics['recent_errors']) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-700">Top Error Types</span>
                    <span class="text-lg font-bold text-yellow-600">{{ count($errorMetrics['top_errors']) }}</span>
                </div>
            </div>
        </div>

        <!-- System Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">System Actions</h3>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <div class="space-y-3">
                <button onclick="window.monitoringDashboard?.clearCache()" class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Clear Cache
                </button>
                <button onclick="window.monitoringDashboard?.testLogging()" class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Test Logging
                </button>
                <button onclick="window.monitoringDashboard?.exportReport()" class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg hover:from-purple-700 hover:to-purple-800 transition-all duration-200 font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export Report
                </button>
            </div>
        </div>
    </div>

    <!-- Recent Logs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Recent System Logs</h3>
            <div class="flex items-center space-x-3">
                <select id="log-level" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Levels</option>
                    <option value="debug">Debug</option>
                    <option value="info">Info</option>
                    <option value="warning">Warning</option>
                    <option value="error">Error</option>
                    <option value="critical">Critical</option>
                </select>
                <select id="service-filter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Services</option>
                    <option value="youtube">YouTube</option>
                    <option value="track_processing">Track Processing</option>
                    <option value="queue">Queue</option>
                </select>
                <button onclick="window.monitoringDashboard?.loadLogs()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors duration-200">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
        <div id="logs-container" class="space-y-3 max-h-96 overflow-y-auto">
            <!-- Logs will be loaded here -->
            <div class="animate-pulse space-y-3">
                <div class="h-16 bg-gray-100 rounded-lg"></div>
                <div class="h-16 bg-gray-100 rounded-lg"></div>
                <div class="h-16 bg-gray-100 rounded-lg"></div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed top-4 right-4 z-50 hidden">
    <div class="px-6 py-3 rounded-lg shadow-lg bg-green-500 text-white">
        <p id="toast-message"></p>
    </div>
</div>
@endsection

@push('scripts')
@vite('resources/js/monitoring-dashboard.js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.monitoringDashboard = new MonitoringDashboard();
});
</script>
@endpush 