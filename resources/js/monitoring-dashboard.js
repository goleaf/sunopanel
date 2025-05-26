class MonitoringDashboard {
    constructor() {
        this.refreshInterval = null;
        this.isAutoRefreshEnabled = true;
        this.refreshRate = 30000; // 30 seconds
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadInitialData();
        this.startAutoRefresh();
    }

    setupEventListeners() {
        // Auto-refresh toggle
        document.getElementById('auto-refresh-toggle')?.addEventListener('change', (e) => {
            this.isAutoRefreshEnabled = e.target.checked;
            if (this.isAutoRefreshEnabled) {
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
            }
        });

        // Refresh rate selector
        document.getElementById('refresh-rate')?.addEventListener('change', (e) => {
            this.refreshRate = parseInt(e.target.value) * 1000;
            if (this.isAutoRefreshEnabled) {
                this.startAutoRefresh();
            }
        });

        // Log level filter
        document.getElementById('log-level')?.addEventListener('change', () => {
            this.loadLogs();
        });

        // Service filter
        document.getElementById('service-filter')?.addEventListener('change', () => {
            this.loadLogs();
        });
    }

    loadInitialData() {
        this.loadRealtimeStats();
        this.loadLogs();
        this.loadMetrics();
    }

    startAutoRefresh() {
        this.stopAutoRefresh();
        if (this.isAutoRefreshEnabled) {
            this.refreshInterval = setInterval(() => {
                this.loadRealtimeStats();
                this.loadLogs();
                this.updateLastRefreshTime();
            }, this.refreshRate);
        }
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    async loadRealtimeStats() {
        try {
            const response = await this.fetchWithErrorHandling('/monitoring/realtime');
            if (response.success) {
                this.updateRealtimeStats(response.data);
                this.updateSystemHealthIndicators(response.data);
            }
        } catch (error) {
            this.handleError('Failed to load realtime stats', error);
        }
    }

    async loadLogs() {
        try {
            const level = document.getElementById('log-level')?.value || '';
            const service = document.getElementById('service-filter')?.value || '';
            const limit = document.getElementById('log-limit')?.value || 100;
            
            const params = new URLSearchParams();
            if (level) params.append('level', level);
            if (service) params.append('service', service);
            if (limit) params.append('limit', limit);

            const url = `/monitoring/logs${params.toString() ? '?' + params.toString() : ''}`;
            const response = await this.fetchWithErrorHandling(url);
            
            if (response.success) {
                this.updateLogsDisplay(response.data);
            }
        } catch (error) {
            this.handleError('Failed to load logs', error);
        }
    }

    async loadMetrics() {
        try {
            const response = await this.fetchWithErrorHandling('/monitoring/metrics');
            if (response.success) {
                this.updateMetricsDisplay(response.data);
            }
        } catch (error) {
            this.handleError('Failed to load metrics', error);
        }
    }

    updateRealtimeStats(stats) {
        // Update memory usage
        this.updateProgressBar('memory-usage-bar', stats.memory_usage.percentage);
        this.updateElement('memory-usage-text', `${stats.memory_usage.percentage}%`);
        this.updateElement('memory-current', this.formatBytes(stats.memory_usage.current));
        this.updateElement('memory-peak', this.formatBytes(stats.memory_usage.peak));

        // Update disk usage
        this.updateProgressBar('disk-usage-bar', stats.disk_usage.percentage);
        this.updateElement('disk-usage-text', `${stats.disk_usage.percentage}%`);
        this.updateElement('disk-free', this.formatBytes(stats.disk_usage.free));
        this.updateElement('disk-total', this.formatBytes(stats.disk_usage.total));

        // Update cache status
        this.updateElement('cache-status', stats.cache_status.status);
        this.updateElement('cache-read-time', `${stats.cache_status.read_time_ms}ms`);
        this.updateElement('cache-write-time', `${stats.cache_status.write_time_ms}ms`);

        // Update queue status
        this.updateElement('queue-pending', stats.queue_status.pending);
        this.updateElement('queue-failed', stats.queue_status.failed);
        this.updateElement('queue-processed', stats.queue_status.processed);
    }

    updateSystemHealthIndicators(stats) {
        // Update health indicators with color coding
        const indicators = {
            'database-indicator': stats.database?.connected,
            'redis-indicator': stats.redis?.connected,
            'cache-indicator': stats.cache_status?.status === 'healthy',
            'queue-indicator': stats.queue_status?.pending < 100
        };

        Object.entries(indicators).forEach(([id, isHealthy]) => {
            const element = document.getElementById(id);
            if (element) {
                element.className = `w-3 h-3 rounded-full ${isHealthy ? 'bg-green-500' : 'bg-red-500'}`;
            }
        });
    }

    updateLogsDisplay(logs) {
        const container = document.getElementById('logs-container');
        if (!container) return;

        if (logs.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-4">No logs found</p>';
            return;
        }

        container.innerHTML = logs.map(log => this.createLogEntry(log)).join('');
    }

    createLogEntry(log) {
        const levelColor = this.getLevelColor(log.level);
        const levelBadgeColor = this.getLevelBadgeColor(log.level);
        
        return `
            <div class="border-l-4 ${levelColor} bg-gray-50 p-3 rounded hover:bg-gray-100 transition-colors">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">${this.escapeHtml(log.message)}</p>
                        <p class="text-xs text-gray-500 mt-1">${this.formatTimestamp(log.timestamp)}</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded ${levelBadgeColor}">
                        ${log.level.toUpperCase()}
                    </span>
                </div>
                ${log.context ? `<div class="mt-2 text-xs text-gray-600">${this.escapeHtml(JSON.stringify(log.context))}</div>` : ''}
            </div>
        `;
    }

    updateMetricsDisplay(metrics) {
        // Update application metrics
        if (metrics.application) {
            this.updateElement('total-tracks', this.formatNumber(metrics.application.total_tracks));
            this.updateElement('total-genres', this.formatNumber(metrics.application.total_genres));
            this.updateElement('pending-jobs', this.formatNumber(metrics.application.pending_jobs));
            this.updateElement('failed-jobs', this.formatNumber(metrics.application.failed_jobs));
        }

        // Update performance metrics
        if (metrics.performance) {
            this.updateElement('execution-time', `${metrics.performance.execution_time.toFixed(3)}s`);
            this.updateElement('memory-limit', metrics.performance.memory_limit);
        }

        // Update error metrics
        if (metrics.errors) {
            this.updateElement('error-rate', `${(metrics.errors.error_rate * 100).toFixed(2)}%`);
            this.updateElement('recent-errors', this.formatNumber(metrics.errors.recent_errors));
        }
    }

    async clearCache() {
        try {
            const response = await this.fetchWithErrorHandling('/monitoring/clear-cache', {
                method: 'POST'
            });
            
            this.showToast(response.message, response.success ? 'success' : 'error');
            
            if (response.success) {
                this.loadRealtimeStats();
            }
        } catch (error) {
            this.handleError('Failed to clear cache', error);
        }
    }

    async testLogging() {
        try {
            const response = await this.fetchWithErrorHandling('/monitoring/test-logging', {
                method: 'POST'
            });
            
            this.showToast(response.message, response.success ? 'success' : 'error');
            
            if (response.success) {
                setTimeout(() => this.loadLogs(), 1000);
            }
        } catch (error) {
            this.handleError('Failed to test logging', error);
        }
    }

    async exportReport() {
        try {
            const response = await this.fetchWithErrorHandling('/monitoring/export-report');
            
            if (response.success) {
                this.downloadReport(response.data);
                this.showToast('Health report exported successfully', 'success');
            } else {
                this.showToast(response.message, 'error');
            }
        } catch (error) {
            this.handleError('Failed to export report', error);
        }
    }

    downloadReport(data) {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `system-health-report-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    refreshData() {
        this.loadRealtimeStats();
        this.loadLogs();
        this.loadMetrics();
        this.showToast('Data refreshed successfully', 'success');
    }

    // Utility methods
    async fetchWithErrorHandling(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        };

        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    }

    updateElement(id, content) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = content;
        }
    }

    updateProgressBar(id, percentage) {
        const element = document.getElementById(id);
        if (element) {
            element.style.width = `${Math.min(percentage, 100)}%`;
            
            // Update color based on percentage
            const colorClass = percentage > 80 ? 'bg-red-600' : 
                              percentage > 60 ? 'bg-yellow-600' : 'bg-green-600';
            element.className = `h-2 rounded-full transition-all duration-300 ${colorClass}`;
        }
    }

    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    formatTimestamp(timestamp) {
        return new Date(timestamp).toLocaleString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getLevelColor(level) {
        const colors = {
            debug: 'border-gray-400',
            info: 'border-blue-400',
            warning: 'border-yellow-400',
            error: 'border-red-400',
            critical: 'border-red-600'
        };
        return colors[level] || 'border-gray-400';
    }

    getLevelBadgeColor(level) {
        const colors = {
            debug: 'bg-gray-100 text-gray-800',
            info: 'bg-blue-100 text-blue-800',
            warning: 'bg-yellow-100 text-yellow-800',
            error: 'bg-red-100 text-red-800',
            critical: 'bg-red-200 text-red-900'
        };
        return colors[level] || 'bg-gray-100 text-gray-800';
    }

    updateLastRefreshTime() {
        const element = document.getElementById('last-refresh-time');
        if (element) {
            element.textContent = new Date().toLocaleTimeString();
        }
    }

    showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        
        if (!toast || !toastMessage) return;
        
        toastMessage.textContent = message;
        
        const toastDiv = toast.querySelector('div');
        const bgColor = type === 'success' ? 'bg-green-500' : 
                       type === 'warning' ? 'bg-yellow-500' : 'bg-red-500';
        toastDiv.className = `px-6 py-3 rounded-lg shadow-lg ${bgColor} text-white`;
        
        toast.classList.remove('hidden');
        
        setTimeout(() => {
            toast.classList.add('hidden');
        }, 3000);
    }

    handleError(message, error) {
        console.error(message, error);
        this.showToast(`${message}: ${error.message}`, 'error');
    }
}

// Global functions for button clicks
window.clearCache = () => window.monitoringDashboard?.clearCache();
window.testLogging = () => window.monitoringDashboard?.testLogging();
window.exportReport = () => window.monitoringDashboard?.exportReport();
window.refreshData = () => window.monitoringDashboard?.refreshData();

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.monitoringDashboard = new MonitoringDashboard();
}); 