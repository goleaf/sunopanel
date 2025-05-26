@extends('layouts.app')

@section('title', 'System Monitoring Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">System Monitoring Dashboard</h1>
        <p class="text-gray-600">Real-time system health, performance metrics, and error tracking</p>
    </div>

    <!-- System Health Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Database Health -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full {{ $systemHealth['database']['connected'] ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7c0-2.21-1.79-4-4-4H8c-2.21 0-4 1.79-4 4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Database</p>
                    <p class="text-2xl font-bold {{ $systemHealth['database']['connected'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $systemHealth['database']['connected'] ? 'Connected' : 'Disconnected' }}
                    </p>
                    @if($systemHealth['database']['connected'])
                        <p class="text-xs text-gray-500">{{ $systemHealth['database']['response_time_ms'] }}ms</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Redis Health -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full {{ $systemHealth['redis']['connected'] ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Redis Cache</p>
                    <p class="text-2xl font-bold {{ $systemHealth['redis']['connected'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $systemHealth['redis']['connected'] ? 'Connected' : 'Disconnected' }}
                    </p>
                    @if($systemHealth['redis']['connected'])
                        <p class="text-xs text-gray-500">{{ $systemHealth['redis']['response_time_ms'] }}ms</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Queue Health -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Queue Jobs</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($systemHealth['queue']['pending_jobs']) }}</p>
                    <p class="text-xs text-gray-500">{{ number_format($systemHealth['queue']['failed_jobs']) }} failed</p>
                </div>
            </div>
        </div>

        <!-- Storage Health -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 011 1v1a1 1 0 01-1 1h-1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7H3a1 1 0 01-1-1V5a1 1 0 011-1h4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Disk Usage</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $systemHealth['storage']['disk_usage_percent'] }}%</p>
                    <p class="text-xs text-gray-500">{{ number_format($systemHealth['storage']['disk_free_space'] / 1024 / 1024 / 1024, 1) }}GB free</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Memory Usage -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Memory Usage</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Current Usage</span>
                        <span>{{ number_format($performanceMetrics['memory_usage']['current'] / 1024 / 1024, 1) }}MB</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(($performanceMetrics['memory_usage']['current'] / $performanceMetrics['memory_usage']['peak']) * 100, 100) }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Peak Usage</span>
                        <span>{{ number_format($performanceMetrics['memory_usage']['peak'] / 1024 / 1024, 1) }}MB</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-orange-600 h-2 rounded-full" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Stats -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Real-time Statistics</h3>
            <div id="realtime-stats" class="space-y-4">
                <!-- Real-time stats will be loaded here -->
                <div class="animate-pulse">
                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Metrics and Logs -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Error Metrics -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Error Metrics</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600">Error Rate</span>
                    <span class="text-lg font-bold text-red-600">{{ number_format($errorMetrics['error_rate'] * 100, 2) }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600">Recent Errors</span>
                    <span class="text-lg font-bold text-orange-600">{{ count($errorMetrics['recent_errors']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600">Top Errors</span>
                    <span class="text-lg font-bold text-yellow-600">{{ count($errorMetrics['top_errors']) }}</span>
                </div>
            </div>
        </div>

        <!-- System Actions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Actions</h3>
            <div class="space-y-3">
                <button onclick="clearCache()" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200">
                    Clear Cache
                </button>
                <button onclick="testLogging()" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition duration-200">
                    Test Logging
                </button>
                <button onclick="exportReport()" class="w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 transition duration-200">
                    Export Health Report
                </button>
                <button onclick="refreshData()" class="w-full bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-700 transition duration-200">
                    Refresh Data
                </button>
            </div>
        </div>
    </div>

    <!-- Recent Logs -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Recent Logs</h3>
            <div class="flex space-x-2">
                <select id="log-level" class="px-3 py-1 border border-gray-300 rounded-md text-sm">
                    <option value="">All Levels</option>
                    <option value="debug">Debug</option>
                    <option value="info">Info</option>
                    <option value="warning">Warning</option>
                    <option value="error">Error</option>
                    <option value="critical">Critical</option>
                </select>
                <button onclick="loadLogs()" class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                    Refresh
                </button>
            </div>
        </div>
        <div id="logs-container" class="space-y-2 max-h-96 overflow-y-auto">
            <!-- Logs will be loaded here -->
            <div class="animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
                <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">System Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-600">PHP Version</p>
                <p class="text-lg font-bold text-gray-900">{{ PHP_VERSION }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Laravel Version</p>
                <p class="text-lg font-bold text-gray-900">{{ app()->version() }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Environment</p>
                <p class="text-lg font-bold text-gray-900">{{ app()->environment() }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Timezone</p>
                <p class="text-lg font-bold text-gray-900">{{ config('app.timezone') }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Memory Limit</p>
                <p class="text-lg font-bold text-gray-900">{{ ini_get('memory_limit') }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Max Execution Time</p>
                <p class="text-lg font-bold text-gray-900">{{ ini_get('max_execution_time') }}s</p>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed top-4 right-4 z-50 hidden">
    <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
        <span id="toast-message"></span>
    </div>
</div>

@push('scripts')
<script>
let refreshInterval;

document.addEventListener('DOMContentLoaded', function() {
    loadLogs();
    loadRealtimeStats();
    
    // Auto-refresh every 30 seconds
    refreshInterval = setInterval(() => {
        loadRealtimeStats();
        loadLogs();
    }, 30000);
});

async function loadRealtimeStats() {
    try {
        const response = await fetch('/monitoring/realtime');
        const data = await response.json();
        
        if (data.success) {
            updateRealtimeStats(data.data);
        }
    } catch (error) {
        console.error('Failed to load realtime stats:', error);
    }
}

function updateRealtimeStats(stats) {
    const container = document.getElementById('realtime-stats');
    container.innerHTML = `
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-600">Memory Usage</span>
            <span class="text-lg font-bold text-blue-600">${stats.memory_usage.percentage}%</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-600">Disk Usage</span>
            <span class="text-lg font-bold text-purple-600">${stats.disk_usage.percentage}%</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-600">Cache Status</span>
            <span class="text-lg font-bold ${stats.cache_status.status === 'healthy' ? 'text-green-600' : 'text-red-600'}">${stats.cache_status.status}</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-600">Queue Jobs</span>
            <span class="text-lg font-bold text-orange-600">${stats.queue_status.pending}</span>
        </div>
    `;
}

async function loadLogs() {
    try {
        const level = document.getElementById('log-level').value;
        const url = `/monitoring/logs${level ? `?level=${level}` : ''}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            updateLogsDisplay(data.data);
        }
    } catch (error) {
        console.error('Failed to load logs:', error);
    }
}

function updateLogsDisplay(logs) {
    const container = document.getElementById('logs-container');
    
    if (logs.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No logs found</p>';
        return;
    }
    
    container.innerHTML = logs.map(log => `
        <div class="border-l-4 ${getLevelColor(log.level)} bg-gray-50 p-3 rounded">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">${log.message}</p>
                    <p class="text-xs text-gray-500 mt-1">${log.timestamp}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded ${getLevelBadgeColor(log.level)}">${log.level.toUpperCase()}</span>
            </div>
        </div>
    `).join('');
}

function getLevelColor(level) {
    const colors = {
        debug: 'border-gray-400',
        info: 'border-blue-400',
        warning: 'border-yellow-400',
        error: 'border-red-400',
        critical: 'border-red-600'
    };
    return colors[level] || 'border-gray-400';
}

function getLevelBadgeColor(level) {
    const colors = {
        debug: 'bg-gray-100 text-gray-800',
        info: 'bg-blue-100 text-blue-800',
        warning: 'bg-yellow-100 text-yellow-800',
        error: 'bg-red-100 text-red-800',
        critical: 'bg-red-200 text-red-900'
    };
    return colors[level] || 'bg-gray-100 text-gray-800';
}

async function clearCache() {
    try {
        const response = await fetch('/monitoring/clear-cache', { method: 'POST' });
        const data = await response.json();
        
        showToast(data.message, data.success ? 'success' : 'error');
    } catch (error) {
        showToast('Failed to clear cache', 'error');
    }
}

async function testLogging() {
    try {
        const response = await fetch('/monitoring/test-logging', { method: 'POST' });
        const data = await response.json();
        
        showToast(data.message, data.success ? 'success' : 'error');
        
        if (data.success) {
            setTimeout(loadLogs, 1000); // Reload logs after 1 second
        }
    } catch (error) {
        showToast('Failed to test logging', 'error');
    }
}

async function exportReport() {
    try {
        const response = await fetch('/monitoring/export-report');
        const data = await response.json();
        
        if (data.success) {
            const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `system-health-report-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            showToast('Health report exported successfully', 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Failed to export report', 'error');
    }
}

function refreshData() {
    loadRealtimeStats();
    loadLogs();
    showToast('Data refreshed successfully', 'success');
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    
    toastMessage.textContent = message;
    
    // Update toast color based on type
    const toastDiv = toast.querySelector('div');
    toastDiv.className = `px-6 py-3 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
    
    toast.classList.remove('hidden');
    
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 3000);
}

// Add CSRF token to all requests
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    // Add CSRF token to fetch requests
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        if (options.method && options.method.toUpperCase() !== 'GET') {
            options.headers = {
                ...options.headers,
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
        }
        return originalFetch(url, options);
    };
}
</script>
@endpush
@endsection 