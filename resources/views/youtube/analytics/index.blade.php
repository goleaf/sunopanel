@extends('layouts.app')

@section('title', 'YouTube Analytics')

@section('head')
    @vite(['resources/js/youtube-analytics.js'])
@endsection

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                YouTube Analytics
            </h1>
            <p class="text-gray-600 mt-1">Track performance and engagement metrics for your YouTube uploads</p>
        </div>
        
        <div class="flex gap-2 mt-4 md:mt-0">
            <button onclick="refreshAnalytics()" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh Data
            </button>
            <button onclick="bulkUpdateAnalytics()" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                Update Analytics
            </button>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h3 class="card-title text-lg">Total Tracks</h3>
                <div class="stat">
                    <div class="stat-value text-primary" id="total-tracks">{{ $summary['total_tracks'] ?? 0 }}</div>
                    <div class="stat-desc">Uploaded to YouTube</div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h3 class="card-title text-lg">Total Views</h3>
                <div class="stat">
                    <div class="stat-value text-success" id="total-views">{{ number_format($summary['total_views'] ?? 0) }}</div>
                    <div class="stat-desc">Across all videos</div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h3 class="card-title text-lg">Total Likes</h3>
                <div class="stat">
                    <div class="stat-value text-warning" id="total-likes">{{ number_format($summary['total_likes'] ?? 0) }}</div>
                    <div class="stat-desc">Total engagement</div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h3 class="card-title text-lg">Avg. Engagement</h3>
                <div class="stat">
                    <div class="stat-value text-info" id="avg-engagement">{{ $summary['average_engagement_rate'] ?? 0 }}%</div>
                    <div class="stat-desc">Engagement rate</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Trends Chart -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Performance Trends
                </h2>
                
                <div class="flex gap-2 mb-4">
                    <select id="trend-period" class="select select-bordered select-sm">
                        <option value="7d">Last 7 days</option>
                        <option value="30d" selected>Last 30 days</option>
                        <option value="90d">Last 90 days</option>
                        <option value="1y">Last year</option>
                    </select>
                    <select id="trend-metric" class="select select-bordered select-sm">
                        <option value="views" selected>Views</option>
                        <option value="likes">Likes</option>
                        <option value="comments">Comments</option>
                        <option value="engagement">Engagement Rate</option>
                    </select>
                </div>
                
                <div id="trends-chart" class="h-64 flex items-center justify-center">
                    <div class="loading loading-spinner loading-lg"></div>
                </div>
            </div>
        </div>

        <!-- Top Performing Tracks -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    Top Performing
                </h2>
                
                <div class="flex gap-2 mb-4">
                    <select id="top-metric" class="select select-bordered select-sm">
                        <option value="views" selected>By Views</option>
                        <option value="likes">By Likes</option>
                        <option value="comments">By Comments</option>
                        <option value="engagement">By Engagement</option>
                    </select>
                    <select id="top-period" class="select select-bordered select-sm">
                        <option value="7d">Last 7 days</option>
                        <option value="30d">Last 30 days</option>
                        <option value="90d">Last 90 days</option>
                        <option value="all" selected>All time</option>
                    </select>
                </div>
                
                <div id="top-tracks" class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($topTracks->take(5) as $track)
                        <div class="flex items-center justify-between p-2 bg-base-200 rounded">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ $track->title }}</p>
                                <p class="text-xs text-gray-500">{{ $track->artist }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold">{{ $track->formatted_view_count }}</p>
                                <p class="text-xs text-gray-500">{{ $track->formatted_like_count }} likes</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Uploads -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Recent Uploads
            </h2>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Track</th>
                            <th>Artist</th>
                            <th>Genre</th>
                            <th>Views</th>
                            <th>Likes</th>
                            <th>Comments</th>
                            <th>Engagement</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTracks as $track)
                            <tr data-track-id="{{ $track->id }}">
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar">
                                            <div class="w-8 h-8 rounded bg-primary text-primary-content flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-bold text-sm">{{ Str::limit($track->title, 30) }}</div>
                                            @if($track->youtube_url)
                                                <a href="{{ $track->youtube_url }}" target="_blank" class="text-xs text-blue-600 hover:underline">
                                                    View on YouTube
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-sm">{{ $track->artist ?? 'Unknown' }}</td>
                                <td class="text-sm">
                                    @if($track->genre)
                                        <span class="badge badge-outline badge-sm">{{ $track->genre->name }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="text-sm font-mono">
                                    <span class="track-views">{{ $track->formatted_view_count }}</span>
                                </td>
                                <td class="text-sm font-mono">
                                    <span class="track-likes">{{ $track->formatted_like_count }}</span>
                                </td>
                                <td class="text-sm font-mono">
                                    <span class="track-comments">{{ number_format($track->youtube_comment_count ?? 0) }}</span>
                                </td>
                                <td class="text-sm">
                                    <span class="badge badge-sm {{ $track->engagement_rate > 5 ? 'badge-success' : ($track->engagement_rate > 2 ? 'badge-warning' : 'badge-ghost') }}">
                                        {{ $track->engagement_rate }}%
                                    </span>
                                </td>
                                <td class="text-xs text-gray-500">
                                    {{ $track->youtube_uploaded_at?->diffForHumans() ?? 'Unknown' }}
                                </td>
                                <td>
                                    <div class="flex gap-1">
                                        <button onclick="updateTrackAnalytics({{ $track->id }})" class="btn btn-xs btn-ghost tooltip" data-tip="Update analytics">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button onclick="viewTrackDetails({{ $track->id }})" class="btn btn-xs btn-ghost tooltip" data-tip="View details">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-gray-500 py-8">
                                    No tracks uploaded to YouTube yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Track Details Modal -->
<dialog id="track-details-modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <h3 class="font-bold text-lg mb-4">Track Analytics Details</h3>
        
        <div id="track-details-content">
            <div class="loading loading-spinner loading-lg mx-auto"></div>
        </div>
        
        <div class="modal-action">
            <form method="dialog">
                <button class="btn">Close</button>
            </form>
        </div>
    </div>
</dialog>

<script>
// Global variables
let trendsChart = null;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeTrendsChart();
    setupEventListeners();
    loadTopTracks();
});

function setupEventListeners() {
    // Trend chart controls
    document.getElementById('trend-period').addEventListener('change', updateTrendsChart);
    document.getElementById('trend-metric').addEventListener('change', updateTrendsChart);
    
    // Top tracks controls
    document.getElementById('top-metric').addEventListener('change', loadTopTracks);
    document.getElementById('top-period').addEventListener('change', loadTopTracks);
}

function initializeTrendsChart() {
    updateTrendsChart();
}

function updateTrendsChart() {
    const period = document.getElementById('trend-period').value;
    const metric = document.getElementById('trend-metric').value;
    
    const chartContainer = document.getElementById('trends-chart');
    chartContainer.innerHTML = '<div class="loading loading-spinner loading-lg"></div>';
    
    fetch(`/youtube/analytics/trends?period=${period}&metric=${metric}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTrendsChart(data.data.trends, metric);
            } else {
                chartContainer.innerHTML = '<div class="text-center text-error">Failed to load trends data</div>';
            }
        })
        .catch(error => {
            console.error('Error loading trends:', error);
            chartContainer.innerHTML = '<div class="text-center text-error">Error loading trends data</div>';
        });
}

function renderTrendsChart(trends, metric) {
    const chartContainer = document.getElementById('trends-chart');
    
    if (!trends || trends.length === 0) {
        chartContainer.innerHTML = '<div class="text-center text-gray-500">No data available for this period</div>';
        return;
    }
    
    // Simple chart implementation using CSS
    const maxValue = Math.max(...trends.map(t => t.value));
    const chartHtml = `
        <div class="w-full h-full flex items-end justify-between gap-1 px-2">
            ${trends.map(trend => {
                const height = maxValue > 0 ? (trend.value / maxValue) * 100 : 0;
                return `
                    <div class="flex flex-col items-center flex-1">
                        <div class="tooltip tooltip-top" data-tip="${trend.value} ${metric} on ${trend.date}">
                            <div class="bg-primary rounded-t w-full" style="height: ${height}%; min-height: 2px;"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1 transform rotate-45 origin-left">
                            ${new Date(trend.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
    
    chartContainer.innerHTML = chartHtml;
}

function loadTopTracks() {
    const metric = document.getElementById('top-metric').value;
    const period = document.getElementById('top-period').value;
    
    const container = document.getElementById('top-tracks');
    container.innerHTML = '<div class="loading loading-spinner loading-lg mx-auto"></div>';
    
    fetch(`/youtube/analytics/top-performing?metric=${metric}&period=${period}&limit=5`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTopTracks(data.data.tracks);
            } else {
                container.innerHTML = '<div class="text-center text-error">Failed to load top tracks</div>';
            }
        })
        .catch(error => {
            console.error('Error loading top tracks:', error);
            container.innerHTML = '<div class="text-center text-error">Error loading top tracks</div>';
        });
}

function renderTopTracks(tracks) {
    const container = document.getElementById('top-tracks');
    
    if (!tracks || tracks.length === 0) {
        container.innerHTML = '<div class="text-center text-gray-500">No tracks found for this period</div>';
        return;
    }
    
    const tracksHtml = tracks.map(track => `
        <div class="flex items-center justify-between p-2 bg-base-200 rounded">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">${track.title}</p>
                <p class="text-xs text-gray-500">${track.artist || 'Unknown'}</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-bold">${track.formatted_views}</p>
                <p class="text-xs text-gray-500">${track.formatted_likes} likes</p>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = tracksHtml;
}

function refreshAnalytics() {
    // Refresh all data
    location.reload();
}

function bulkUpdateAnalytics() {
    if (!confirm('This will update analytics for all tracks. This may take a while. Continue?')) {
        return;
    }
    
    const button = event.target;
    button.classList.add('loading');
    button.disabled = true;
    
    fetch('/youtube/analytics/bulk-update', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ limit: 50, force: false })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update analytics');
    })
    .finally(() => {
        button.classList.remove('loading');
        button.disabled = false;
    });
}

function updateTrackAnalytics(trackId) {
    const button = event.target.closest('button');
    button.classList.add('loading');
    button.disabled = true;
    
    fetch(`/youtube/analytics/tracks/${trackId}/update`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the row with new data
            const row = document.querySelector(`[data-track-id="${trackId}"]`);
            if (row && data.data) {
                row.querySelector('.track-views').textContent = data.data.view_count?.toLocaleString() || '0';
                row.querySelector('.track-likes').textContent = data.data.like_count?.toLocaleString() || '0';
                row.querySelector('.track-comments').textContent = data.data.comment_count?.toLocaleString() || '0';
                
                const engagementBadge = row.querySelector('.badge');
                if (engagementBadge) {
                    engagementBadge.textContent = `${data.data.engagement_rate || 0}%`;
                }
            }
            
            alert('Analytics updated successfully');
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update analytics');
    })
    .finally(() => {
        button.classList.remove('loading');
        button.disabled = false;
    });
}

function viewTrackDetails(trackId) {
    const modal = document.getElementById('track-details-modal');
    const content = document.getElementById('track-details-content');
    
    content.innerHTML = '<div class="loading loading-spinner loading-lg mx-auto"></div>';
    modal.showModal();
    
    fetch(`/youtube/analytics/tracks/${trackId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTrackDetails(data.data);
            } else {
                content.innerHTML = '<div class="text-center text-error">Failed to load track details</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="text-center text-error">Error loading track details</div>';
        });
}

function renderTrackDetails(analytics) {
    const content = document.getElementById('track-details-content');
    
    const detailsHtml = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="stat">
                <div class="stat-title">Views</div>
                <div class="stat-value text-primary">${analytics.view_count?.toLocaleString() || 0}</div>
            </div>
            <div class="stat">
                <div class="stat-title">Likes</div>
                <div class="stat-value text-success">${analytics.like_count?.toLocaleString() || 0}</div>
            </div>
            <div class="stat">
                <div class="stat-title">Comments</div>
                <div class="stat-value text-warning">${analytics.comment_count?.toLocaleString() || 0}</div>
            </div>
            <div class="stat">
                <div class="stat-title">Favorites</div>
                <div class="stat-value text-info">${analytics.favorite_count?.toLocaleString() || 0}</div>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="space-y-2">
            <div><strong>Title:</strong> ${analytics.title || 'N/A'}</div>
            <div><strong>Published:</strong> ${analytics.published_at ? new Date(analytics.published_at).toLocaleDateString() : 'N/A'}</div>
            <div><strong>Duration:</strong> ${analytics.duration || 'N/A'}</div>
            <div><strong>Definition:</strong> ${analytics.definition || 'N/A'}</div>
            <div><strong>Privacy Status:</strong> ${analytics.privacy_status || 'N/A'}</div>
            <div><strong>Upload Status:</strong> ${analytics.upload_status || 'N/A'}</div>
        </div>
    `;
    
    content.innerHTML = detailsHtml;
}
</script>
@endsection 