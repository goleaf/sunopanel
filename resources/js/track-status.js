/**
 * Track Status API Module
 * 
 * This module provides functions for interacting with the track status API
 * and efficiently updating track statuses on different pages.
 */

// Class definition for track status API client
class TrackStatusAPI {
    /**
     * Initialize the track status updater
     * 
     * @param {Object} options - Configuration options
     * @param {Number} options.interval - Update interval in ms (default: 500ms)
     * @param {Function} options.onUpdate - Callback when status is updated (optional)
     * @param {Boolean} options.useBulk - Whether to use bulk API for multiple tracks (default: true)
     * @param {Boolean} options.autoReload - Auto-reload the page after a certain time
     * @param {Number} options.reloadInterval - How often to reload the page (30 seconds)
     * @param {Boolean} options.hideCompleted - Hide completed tracks
     * @param {Number} options.timeout - Request timeout in milliseconds (default: 10000)
     * @param {Number} options.maxRetries - Maximum number of retries for failed requests (default: 3)
     * @param {Boolean} options.debug - Enable debug logging
     */
    constructor(options = {}) {
        this.options = Object.assign({
            interval: 500, // default to 500ms for active checks
            timeout: 10000, // request timeout in milliseconds
            maxRetries: 3, // maximum number of retries for failed requests
            debug: false, // enable debug logging
            useBulk: true,
            autoReload: true,          // Auto-reload the page after a certain time
            reloadInterval: 3000,     // How often to reload the page (30 seconds)
            hideCompleted: false,      // Hide completed tracks
        }, options);
        
        this.tracksToWatch = {};
        this.updateTimer = null;
        this.isUpdating = false;
        this.retryCount = 0;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // Track data storage
        this.lastResponse = null;
        this.lastStatusData = {};
        
        // Add support for track visibility filters
        this.visibilityFilters = {
            completed: !this.options.hideCompleted,
            processing: true,
            pending: true, 
            failed: true,
            stopped: true
        };
        
        // Add filter control methods to window
        window.trackFilters = {
            toggleCompleted: this.toggleCompletedVisibility.bind(this),
            toggleFailed: this.toggleFailedVisibility.bind(this),
            showAll: this.showAllTracks.bind(this),
            hideCompleted: this.hideCompletedTracks.bind(this),
            countByStatus: this.countTracksByStatus.bind(this)
        };
        
        // Initialize animation utilities
        this.initAnimationUtils();
        
        // Initialize bulk action buttons
        this.initBulkActionButtons();
    }
    
    /**
     * Initialize animation utilities
     * 
     * @private
     */
    initAnimationUtils() {
        // Add the CSS animation classes if they don't exist
        const styleId = 'track-status-animations-style';
        if (!document.getElementById(styleId)) {
            const animationStyles = `
                @keyframes number-count {
                    from { content: attr(data-from); }
                    to { content: attr(data-to); }
                }
                
                @keyframes progress-flash {
                    0% { background-color: transparent; }
                    25% { background-color: rgba(255, 153, 0, 0.2); }
                    100% { background-color: transparent; }
                }
                
                .track-row.updating {
                    animation: progress-flash 1s ease-out;
                }
                
                .progress-percentage.updating {
                    position: relative;
                    font-weight: bold;
                    color: #ff9900;
                }
            `;
            
            const style = document.createElement('style');
            style.id = styleId;
            style.textContent = animationStyles;
            document.head.appendChild(style);
        }
        
        // Define a global animation helper object
        window.trackAnimations = {
            /**
             * Animate a numeric value change
             * 
             * @param {HTMLElement} element - The element to animate
             * @param {Number} from - Starting value
             * @param {Number} to - Ending value
             * @param {Number} duration - Animation duration in ms (default: 800)
             * @param {String} suffix - Value suffix (default: '%')
             */
            animateNumber: (element, from, to, duration = 800, suffix = '%') => {
                if (!element) return;
                
                const start = Date.now();
                
                // Add highlight class
                element.classList.add('updating');
                
                const animate = () => {
                    const elapsed = Date.now() - start;
                    const progress = Math.min(elapsed / duration, 1);
                    
                    // Calculate the current value using easeOutQuad
                    const easeOut = t => t * (2 - t);
                    const easeProgress = easeOut(progress);
                    const current = Math.round(from + (to - from) * easeProgress);
                    
                    // Update text content
                    element.textContent = `${current}${suffix}`;
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        // Ensure final value and remove highlight
                        element.textContent = `${to}${suffix}`;
                        element.classList.remove('updating');
                    }
                };
                
                animate();
            }
        };
    }
    
    /**
     * Initialize bulk action buttons
     * 
     * @private
     */
    initBulkActionButtons() {
        // Find and attach handlers to bulk action buttons
        document.addEventListener('DOMContentLoaded', () => {
            // Start all tracks button
            const startAllBtn = document.querySelector('.start-all-tracks');
            if (startAllBtn) {
                startAllBtn.addEventListener('click', this.handleStartAllClick.bind(this));
            }
            
            // Stop all tracks button
            const stopAllBtn = document.querySelector('.stop-all-tracks');
            if (stopAllBtn) {
                stopAllBtn.addEventListener('click', this.handleStopAllClick.bind(this));
            }
            
            // Retry all failed tracks button
            const retryFailedBtn = document.querySelector('.retry-all-failed');
            if (retryFailedBtn) {
                retryFailedBtn.addEventListener('click', this.handleRetryAllClick.bind(this));
            }
        });
    }
    
    /**
     * Toggle visibility of completed tracks
     */
    toggleCompletedVisibility() {
        this.visibilityFilters.completed = !this.visibilityFilters.completed;
        this.applyVisibilityFilters();
        return this.visibilityFilters.completed;
    }
    
    /**
     * Toggle visibility of failed tracks
     */
    toggleFailedVisibility() {
        this.visibilityFilters.failed = !this.visibilityFilters.failed;
        this.applyVisibilityFilters();
        return this.visibilityFilters.failed;
    }
    
    /**
     * Show all tracks
     */
    showAllTracks() {
        Object.keys(this.visibilityFilters).forEach(status => {
            this.visibilityFilters[status] = true;
        });
        this.applyVisibilityFilters();
    }
    
    /**
     * Hide completed tracks
     */
    hideCompletedTracks() {
        this.visibilityFilters.completed = false;
        this.applyVisibilityFilters();
    }
    
    /**
     * Apply current visibility filters to all tracks
     */
    applyVisibilityFilters() {
        document.querySelectorAll('tr[data-track-id]').forEach(row => {
            const statusCell = row.querySelector('.track-status');
            if (statusCell) {
                const status = statusCell.dataset.status;
                if (status && this.visibilityFilters[status] !== undefined) {
                    row.style.display = this.visibilityFilters[status] ? '' : 'none';
                }
            }
        });
        
        // Update filter button state
        this.updateFilterButtonState();
    }
    
    /**
     * Update the state of filter buttons
     */
    updateFilterButtonState() {
        // This will update any filter buttons that might exist in the UI
        const filterButtons = document.querySelectorAll('[data-filter-status]');
        filterButtons.forEach(button => {
            const status = button.dataset.filterStatus;
            if (status && this.visibilityFilters[status] !== undefined) {
                if (this.visibilityFilters[status]) {
                    button.classList.add('filter-active');
                } else {
                    button.classList.remove('filter-active');
                }
            }
        });
    }
    
    /**
     * Count tracks by status
     * 
     * @returns {Object} - Counts by status
     */
    countTracksByStatus() {
        const counts = {
            total: Object.keys(this.tracksToWatch).length,
            completed: 0,
            processing: 0,
            pending: 0,
            failed: 0,
            stopped: 0
        };
        
        // Count based on last status data
        for (const trackId in this.lastStatusData) {
            const status = this.lastStatusData[trackId].status;
            if (counts[status] !== undefined) {
                counts[status]++;
            }
        }
        
        return counts;
    }
    
    /**
     * Start the status updater
     */
    start() {
        if (this.updateTimer) {
            this.stop(); // Clear existing timer to avoid duplicates
        }
        
        this.log('Starting track status updater with interval:', this.options.interval);
        
        // Immediately fetch first update
        this.updateTrackStatuses();
        
        // Then set interval for subsequent updates
        this.updateTimer = setInterval(() => {
            this.updateTrackStatuses();
        }, this.options.interval);
        
        return this;
    }
    
    /**
     * Stop the status updater
     */
    stop() {
        if (this.updateTimer) {
            this.log('Stopping track status updater');
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
        return this;
    }
    
    /**
     * Register a track to watch for status updates
     * 
     * @param {string|number} trackId - The ID of the track
     * @param {Object} elements - The elements to update
     * @param {HTMLElement} elements.status - The element to update with status
     * @param {HTMLElement} elements.progress - The element to update with progress
     */
    watchTrack(trackId, elements) {
        if (!trackId) {
            console.error('TrackStatusAPI: watchTrack called without trackId');
            return this;
        }

        if (!elements || !elements.status) {
            console.error(`TrackStatusAPI: watchTrack for track ${trackId} called without required elements`);
            return this;
        }

        this.tracksToWatch[trackId] = elements;
        this.log(`Registered track ${trackId} for status updates`);
        return this;
    }

    /**
     * Remove a track from the watch list
     * 
     * @param {string|number} trackId - The ID of the track
     */
    unwatchTrack(trackId) {
        if (this.tracksToWatch[trackId]) {
            delete this.tracksToWatch[trackId];
            this.log(`Unregistered track ${trackId} from status updates`);
        }
        return this;
    }

    /**
     * Update the status of all watched tracks
     */
    async updateTrackStatuses() {
        if (this.isUpdating) {
            this.log('Update already in progress, skipping');
                return;
            }
            
        const trackIds = Object.keys(this.tracksToWatch);
        if (trackIds.length === 0) {
            this.log('No tracks to update');
                return;
            }
            
        this.isUpdating = true;
        this.log(`Updating status for ${trackIds.length} tracks...`);
            
        try {
            const response = await fetch('/api/tracks/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({ trackIds }),
                signal: AbortSignal.timeout(this.options.timeout)
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Server responded with ${response.status}: ${errorText}`);
            }
            
            const data = await response.json();
            this.lastResponse = data;
            this.retryCount = 0; // Reset retry counter on success

            // Extract active track statuses
            if (data.tracks) {
                const updatedTracks = [];
                
                for (const [trackId, trackData] of Object.entries(data.tracks)) {
                    // Store the latest status data
                    this.lastStatusData[trackId] = trackData;
                    updatedTracks.push(trackData);
                }
                
                // Trigger the onUpdate callback with all track data
                if (typeof this.options.onUpdate === 'function') {
                    this.options.onUpdate(updatedTracks, this);
                }
            }
        } catch (error) {
            this.retryCount++;
            console.error('Error updating track statuses:', error);
            
            if (this.retryCount <= this.options.maxRetries) {
                console.log(`Retry ${this.retryCount}/${this.options.maxRetries} in ${this.options.interval}ms`);
            } else {
                console.error(`Maximum retries (${this.options.maxRetries}) reached. Stopping updater.`);
                this.stop();
            }
        } finally {
            this.isUpdating = false;
        }
    }
    
    /**
     * Get the latest status for a specific track
     * 
     * @param {string|number} trackId 
     * @returns {Object|null} The track status data or null if not found
     */
    getTrackStatus(trackId) {
        return this.lastStatusData[trackId] || null;
    }

    /**
     * Check if there are any active tracks (processing or pending)
     * 
     * @returns {boolean} True if there are active tracks
     */
    hasActiveTracks() {
        if (!this.lastStatusData) return false;
        
        return Object.values(this.lastStatusData).some(track => 
            track.status === 'processing' || track.status === 'pending'
        );
    }
    
    /**
     * Check if there are any failed tracks
     * 
     * @returns {boolean} True if there are failed tracks
     */
    hasFailedTracks() {
        if (!this.lastStatusData) return false;
        
        return Object.values(this.lastStatusData).some(track => 
            track.status === 'failed'
        );
    }

    /**
     * Utility for debug logging
     */
    log(...args) {
        if (this.options.debug) {
            console.log('[TrackStatusAPI]', ...args);
        }
    }

    // Static API methods for track actions
    
    /**
     * Start track processing
     * 
     * @param {string|number} trackId - The track ID
     * @param {boolean} forceRedownload - Whether to force redownload
     * @returns {Promise<Object>} API response
     */
    static async startTrack(trackId, forceRedownload = false) {
            const response = await fetch(`/tracks/${trackId}/start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                },
            body: JSON.stringify({ force_redownload: forceRedownload })
            });
            
            if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || `Failed to start track: ${response.statusText}`);
            }
            
            return await response.json();
    }
    
    /**
     * Stop track processing
     * 
     * @param {string|number} trackId - The track ID
     * @returns {Promise<Object>} API response
     */
    static async stopTrack(trackId) {
            const response = await fetch(`/tracks/${trackId}/stop`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || `Failed to stop track: ${response.statusText}`);
            }
            
            return await response.json();
    }
    
    /**
     * Retry failed track
     * 
     * @param {string|number} trackId - The track ID
     * @returns {Promise<Object>} API response
     */
    static async retryTrack(trackId) {
        const response = await fetch(`/tracks/${trackId}/retry`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || `Failed to retry track: ${response.statusText}`);
            }
            
            return await response.json();
    }
    
    /**
     * Start all tracks (with optional filtering)
     * 
     * @param {Object} filters - Optional filters
     * @returns {Promise<Object>} API response
     */
    static async startAllTracks(filters = {}) {
        const response = await fetch('/api/tracks/start-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(filters)
            });
            
            if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || `Failed to start all tracks: ${response.statusText}`);
            }
            
            return await response.json();
    }
    
    /**
     * Stop all tracks (with optional filtering)
     * 
     * @param {Object} filters - Optional filters
     * @returns {Promise<Object>} API response
     */
    static async stopAllTracks(filters = {}) {
        const response = await fetch('/api/tracks/stop-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(filters)
            });
            
            if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || `Failed to stop all tracks: ${response.statusText}`);
            }
            
            return await response.json();
    }
    
    /**
     * Retry all failed tracks (with optional filtering)
     * 
     * @param {Object} filters - Optional filters
     * @returns {Promise<Object>} API response
     */
    static async retryAllTracks(filters = {}) {
            const response = await fetch('/api/tracks/retry-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(filters)
            });
            
            if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || `Failed to retry all tracks: ${response.statusText}`);
            }
            
            return await response.json();
    }
}

// Export for ES modules
export default TrackStatusAPI;