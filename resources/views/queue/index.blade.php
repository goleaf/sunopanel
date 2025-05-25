@extends('layouts.app')

@section('title', 'Queue Management')

@section('head')
    @vite(['resources/js/queue-dashboard.js'])
@endsection

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Queue Management
            </h1>
            <p class="text-gray-600 mt-1">Monitor and manage background job queues</p>
        </div>
        
        <div class="flex gap-2 mt-4 md:mt-0">
            <button onclick="refreshData()" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
            <div class="form-control">
                <label class="cursor-pointer label">
                    <span class="label-text mr-2">Auto-refresh</span>
                    <input type="checkbox" id="auto-refresh" class="toggle toggle-primary" checked />
                </label>
            </div>
        </div>
    </div>

    <!-- Health Status -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Queue Health Status
            </h2>
            
            <div id="health-status" class="flex items-center gap-4">
                <div class="badge badge-{{ $health['status'] === 'healthy' ? 'success' : ($health['status'] === 'warning' ? 'warning' : 'error') }} badge-lg">
                    {{ ucfirst($health['status']) }}
                </div>
                
                @if(!empty($health['issues']))
                    <div class="text-sm">
                        <strong>Issues:</strong>
                        <ul class="list-disc list-inside ml-2">
                            @foreach($health['issues'] as $issue)
                                <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            
            @if(!empty($health['recommendations']))
                <div class="mt-4">
                    <h3 class="font-semibold text-sm mb-2">Recommendations:</h3>
                    <ul class="list-disc list-inside text-sm text-gray-600">
                        @foreach($health['recommendations'] as $recommendation)
                            <li>{{ $recommendation }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <!-- Queue Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Statistics -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h3 class="card-title text-lg">Total Jobs</h3>
                <div class="stats stats-vertical">
                    <div class="stat">
                        <div class="stat-title">Pending</div>
                        <div class="stat-value text-warning" id="total-pending">{{ $statistics['totals']['pending'] ?? 0 }}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-title">Processing</div>
                        <div class="stat-value text-info" id="total-processing">{{ $statistics['totals']['processing'] ?? 0 }}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-title">Failed</div>
                        <div class="stat-value text-error" id="total-failed">{{ $statistics['failed_jobs'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Individual Queue Stats -->
        @foreach($availableQueues as $queueKey => $queueName)
            @php $queueStats = $statistics[$queueKey] ?? [] @endphp
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h3 class="card-title text-lg">{{ ucwords(str_replace(['_', '-'], ' ', $queueKey)) }}</h3>
                    <div class="stats stats-vertical">
                        <div class="stat">
                            <div class="stat-title">Pending</div>
                            <div class="stat-value text-warning text-sm" data-queue="{{ $queueKey }}" data-metric="pending">
                                {{ $queueStats['pending'] ?? 0 }}
                            </div>
                        </div>
                        <div class="stat">
                            <div class="stat-title">Processing</div>
                            <div class="stat-value text-info text-sm" data-queue="{{ $queueKey }}" data-metric="processing">
                                {{ $queueStats['processing'] ?? 0 }}
                            </div>
                        </div>
                        <div class="stat">
                            <div class="stat-title">Failed</div>
                            <div class="stat-value text-error text-sm" data-queue="{{ $queueKey }}" data-metric="failed">
                                {{ $queueStats['failed'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-actions justify-end mt-4">
                        <button onclick="pauseQueue('{{ $queueName }}')" class="btn btn-xs btn-warning">Pause</button>
                        <button onclick="resumeQueue('{{ $queueName }}')" class="btn btn-xs btn-success">Resume</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Batch Statistics -->
    @if($statistics['batches']['total'] > 0)
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Batch Operations
                </h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="stat">
                        <div class="stat-title">Total Batches</div>
                        <div class="stat-value text-primary" id="batch-total">{{ $statistics['batches']['total'] }}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-title">Pending</div>
                        <div class="stat-value text-warning" id="batch-pending">{{ $statistics['batches']['pending'] }}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-title">Completed</div>
                        <div class="stat-value text-success" id="batch-completed">{{ $statistics['batches']['completed'] }}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-title">Cancelled</div>
                        <div class="stat-value text-error" id="batch-cancelled">{{ $statistics['batches']['cancelled'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Active Batches -->
    @if($activeBatches->isNotEmpty())
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title">Active Batches</h2>
                
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Batch ID</th>
                                <th>Name</th>
                                <th>Progress</th>
                                <th>Jobs</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="active-batches">
                            @foreach($activeBatches as $batch)
                                <tr data-batch-id="{{ $batch['id'] }}">
                                    <td>
                                        <code class="text-xs">{{ substr($batch['id'], 0, 8) }}...</code>
                                    </td>
                                    <td>{{ $batch['name'] }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <progress class="progress progress-primary w-20" value="{{ $batch['progress'] }}" max="100"></progress>
                                            <span class="text-xs">{{ $batch['progress'] }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-xs">
                                            <div>Total: {{ $batch['total_jobs'] }}</div>
                                            <div>Processed: {{ $batch['processed_jobs'] }}</div>
                                            <div>Failed: {{ $batch['failed_jobs'] }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-xs">{{ \Carbon\Carbon::parse($batch['created_at'])->diffForHumans() }}</span>
                                    </td>
                                    <td>
                                        <div class="flex gap-1">
                                            <button onclick="cancelBatch('{{ $batch['id'] }}')" class="btn btn-xs btn-error">Cancel</button>
                                            @if($batch['failed_jobs'] > 0)
                                                <button onclick="retryBatch('{{ $batch['id'] }}')" class="btn btn-xs btn-warning">Retry</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Failed Jobs Management -->
    @if($statistics['failed_jobs'] > 0)
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title text-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    Failed Jobs ({{ $statistics['failed_jobs'] }})
                </h2>
                
                <div class="flex gap-2 mt-4">
                    <button onclick="retryAllFailedJobs()" class="btn btn-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Retry All Failed Jobs
                    </button>
                    <button onclick="clearAllFailedJobs()" class="btn btn-error">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Clear All Failed Jobs
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
let autoRefreshInterval;
let isAutoRefreshEnabled = true;

// Initialize auto-refresh
document.addEventListener('DOMContentLoaded', function() {
    const autoRefreshToggle = document.getElementById('auto-refresh');
    
    autoRefreshToggle.addEventListener('change', function() {
        isAutoRefreshEnabled = this.checked;
        
        if (isAutoRefreshEnabled) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
    
    startAutoRefresh();
});

function startAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    autoRefreshInterval = setInterval(refreshData, 30000); // Refresh every 30 seconds
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

function refreshData() {
    Promise.all([
        fetch('/queue/statistics').then(r => r.json()),
        fetch('/queue/health').then(r => r.json()),
        fetch('/queue/batches').then(r => r.json())
    ]).then(([statsResponse, healthResponse, batchesResponse]) => {
        if (statsResponse.success) {
            updateStatistics(statsResponse.data);
        }
        
        if (healthResponse.success) {
            updateHealthStatus(healthResponse.data);
        }
        
        if (batchesResponse.success) {
            updateActiveBatches(batchesResponse.data);
        }
    }).catch(error => {
        console.error('Error refreshing data:', error);
    });
}

function updateStatistics(stats) {
    // Update total statistics
    document.getElementById('total-pending').textContent = stats.totals.pending || 0;
    document.getElementById('total-processing').textContent = stats.totals.processing || 0;
    document.getElementById('total-failed').textContent = stats.failed_jobs || 0;
    
    // Update individual queue statistics
    document.querySelectorAll('[data-queue]').forEach(element => {
        const queue = element.dataset.queue;
        const metric = element.dataset.metric;
        
        if (stats[queue] && stats[queue][metric] !== undefined) {
            element.textContent = stats[queue][metric];
        }
    });
    
    // Update batch statistics
    if (stats.batches) {
        const batchTotal = document.getElementById('batch-total');
        const batchPending = document.getElementById('batch-pending');
        const batchCompleted = document.getElementById('batch-completed');
        const batchCancelled = document.getElementById('batch-cancelled');
        
        if (batchTotal) batchTotal.textContent = stats.batches.total || 0;
        if (batchPending) batchPending.textContent = stats.batches.pending || 0;
        if (batchCompleted) batchCompleted.textContent = stats.batches.completed || 0;
        if (batchCancelled) batchCancelled.textContent = stats.batches.cancelled || 0;
    }
}

function updateHealthStatus(health) {
    const healthStatus = document.getElementById('health-status');
    const badgeClass = health.status === 'healthy' ? 'badge-success' : 
                      health.status === 'warning' ? 'badge-warning' : 'badge-error';
    
    healthStatus.innerHTML = `
        <div class="badge ${badgeClass} badge-lg">
            ${health.status.charAt(0).toUpperCase() + health.status.slice(1)}
        </div>
    `;
}

function updateActiveBatches(batches) {
    const tbody = document.getElementById('active-batches');
    if (!tbody) return;
    
    // Update existing rows or add new ones
    batches.forEach(batch => {
        const existingRow = document.querySelector(`[data-batch-id="${batch.id}"]`);
        if (existingRow) {
            // Update progress
            const progress = existingRow.querySelector('progress');
            const progressText = existingRow.querySelector('.text-xs');
            if (progress) progress.value = batch.progress;
            if (progressText) progressText.textContent = `${batch.progress}%`;
        }
    });
}

function pauseQueue(queueName) {
    fetch(`/queue/pause/${queueName}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshData();
        } else {
            alert('Error: ' + data.error);
        }
    }).catch(error => {
        console.error('Error:', error);
        alert('Failed to pause queue');
    });
}

function resumeQueue(queueName) {
    fetch(`/queue/resume/${queueName}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshData();
        } else {
            alert('Error: ' + data.error);
        }
    }).catch(error => {
        console.error('Error:', error);
        alert('Failed to resume queue');
    });
}

function cancelBatch(batchId) {
    if (!confirm('Are you sure you want to cancel this batch?')) return;
    
    fetch(`/queue/batches/${batchId}/cancel`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshData();
        } else {
            alert('Error: ' + data.error);
        }
    }).catch(error => {
        console.error('Error:', error);
        alert('Failed to cancel batch');
    });
}

function retryBatch(batchId) {
    fetch(`/queue/batches/${batchId}/retry`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshData();
        } else {
            alert('Error: ' + data.error);
        }
    }).catch(error => {
        console.error('Error:', error);
        alert('Failed to retry batch');
    });
}

function retryAllFailedJobs() {
    if (!confirm('Are you sure you want to retry all failed jobs?')) return;
    
    fetch('/queue/failed-jobs/retry', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshData();
        } else {
            alert('Error: ' + data.error);
        }
    }).catch(error => {
        console.error('Error:', error);
        alert('Failed to retry failed jobs');
    });
}

function clearAllFailedJobs() {
    if (!confirm('Are you sure you want to clear all failed jobs? This action cannot be undone.')) return;
    
    fetch('/queue/failed-jobs/clear', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshData();
        } else {
            alert('Error: ' + data.error);
        }
    }).catch(error => {
        console.error('Error:', error);
        alert('Failed to clear failed jobs');
    });
}
</script>
@endsection 