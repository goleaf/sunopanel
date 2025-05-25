/**
 * YouTube Stats Manager
 * 
 * Handles fetching and displaying YouTube video statistics
 * with configurable auto-refresh functionality.
 */
class YouTubeStatsManager {
    /**
     * Create a new YouTubeStatsManager instance
     * 
     * @param {Object} options Configuration options
     * @param {number} options.refreshInterval Refresh interval in milliseconds (default: 300000ms / 5min)
     * @param {boolean} options.autoRefresh Whether to auto-refresh statistics (default: true)
     * @param {boolean} options.showNotifications Whether to show notifications (default: true)
     * @param {string} options.refreshUrl The URL to fetch stats from (default: '/youtube/refresh-stats')
     * @param {string} options.refreshButtonSelector The selector for the refresh button (default: '#refresh-stats-btn')
     * @param {string} options.totalViewsSelector The selector for the total views element (default: '#total-views')
     * @param {string} options.lastUpdatedSelector The selector for the last updated element (default: '#stats-last-updated')
     */
    constructor(options = {}) {
        // Default options
        this.options = {
            refreshInterval: 300000, // 5 minutes
            autoRefresh: true,
            showNotifications: true,
            refreshUrl: '/youtube/refresh-stats',
            refreshButtonSelector: '#refresh-stats-btn',
            totalViewsSelector: '#total-views',
            lastUpdatedSelector: '#stats-last-updated',
            ...options
        };

        // State
        this.isRefreshing = false;
        this.timer = null;
        this.lastRefreshed = new Date();
        
        // Initialize
        this.init();
    }

    /**
     * Initialize the stats manager
     */
    init() {
        // Set up event listeners
        const refreshButton = document.querySelector(this.options.refreshButtonSelector);
        if (refreshButton) {
            refreshButton.addEventListener('click', () => this.refreshStats());
        }
        
        // Start auto-refresh if enabled
        if (this.options.autoRefresh) {
            this.startAutoRefresh();
        }
    }

    /**
     * Start the auto-refresh timer
     */
    startAutoRefresh() {
        // Clear any existing timer
        if (this.timer) {
            clearInterval(this.timer);
        }
        
        // Set up new timer
        this.timer = setInterval(() => {
            this.refreshStats();
        }, this.options.refreshInterval);
        
        console.log(`Auto-refresh started. Will refresh every ${this.options.refreshInterval / 1000} seconds.`);
    }

    /**
     * Stop the auto-refresh timer
     */
    stopAutoRefresh() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
            console.log('Auto-refresh stopped.');
        }
    }

    /**
     * Refresh YouTube statistics via AJAX
     */
    refreshStats() {
        // Prevent multiple refreshes at once
        if (this.isRefreshing) {
            console.log('Already refreshing stats. Please wait...');
            return;
        }
        
        this.isRefreshing = true;
        
        // Show loading state on the refresh button
        const refreshButton = document.querySelector(this.options.refreshButtonSelector);
        const originalButtonText = refreshButton ? refreshButton.innerHTML : '';
        
        if (refreshButton) {
            refreshButton.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refreshing...';
            refreshButton.disabled = true;
        }
        
        // Get CSRF token
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Make AJAX request
        fetch(this.options.refreshUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Update view counts
            if (data.videoStats) {
                let totalViews = 0;
                
                // Update each video's view count
                const viewCountElements = document.querySelectorAll('.video-view-count');
                viewCountElements.forEach(element => {
                    const videoId = element.dataset.videoId;
                    if (data.videoStats[videoId]) {
                        const viewCount = data.videoStats[videoId].viewCount || 0;
                        element.textContent = new Intl.NumberFormat().format(viewCount);
                        totalViews += parseInt(viewCount);
                    }
                });
                
                // Update total views
                const totalViewsElement = document.querySelector(this.options.totalViewsSelector);
                if (totalViewsElement) {
                    totalViewsElement.textContent = new Intl.NumberFormat().format(totalViews);
                }
            }
            
            // Update last refreshed timestamp
            this.lastRefreshed = new Date();
            this.updateLastUpdatedText();
            
            // Show success notification
            if (this.options.showNotifications) {
                this.showNotification('success', data.message || 'Statistics refreshed successfully');
            }
        })
        .catch(error => {
            console.error('Error refreshing stats:', error);
            
            // Show error notification
            if (this.options.showNotifications) {
                this.showNotification('danger', 'Failed to refresh statistics: ' + error.message);
            }
        })
        .finally(() => {
            // Reset button state
            if (refreshButton) {
                refreshButton.innerHTML = originalButtonText;
                refreshButton.disabled = false;
            }
            
            this.isRefreshing = false;
        });
    }

    /**
     * Update the last updated text
     */
    updateLastUpdatedText() {
        const lastUpdatedElement = document.querySelector(this.options.lastUpdatedSelector);
        if (lastUpdatedElement) {
            lastUpdatedElement.textContent = `Stats last updated: ${this.formatDateTime(this.lastRefreshed)}`;
        }
    }

    /**
     * Format a date for display
     * 
     * @param {Date} date The date to format
     * @returns {string} Formatted date string
     */
    formatDateTime(date) {
        return date.toISOString().replace('T', ' ').substring(0, 19);
    }

    /**
     * Show a notification message
     * 
     * @param {string} type The notification type (success, danger, info, warning)
     * @param {string} message The message to display
     */
    showNotification(type, message) {
        // Create alert element
        const alertElement = document.createElement('div');
        alertElement.className = `alert alert-${type} alert-dismissible fade show`;
        alertElement.setAttribute('role', 'alert');
        
        // Add message and close button
        alertElement.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add to the page
        const container = document.querySelector('.container');
        if (container) {
            const firstRow = container.querySelector('.row');
            if (firstRow) {
                firstRow.appendChild(alertElement);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    alertElement.remove();
                }, 5000);
            }
        }
    }
}

// Make sure the YouTubeStatsManager is globally available
window.YouTubeStatsManager = YouTubeStatsManager;

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = YouTubeStatsManager;
} 