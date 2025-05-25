import TrackStatusAPI from '../track-status';

/**
 * Track Status Manager Module
 * Handles track status updates and UI management for the track listing page
 */
export default class TrackStatusManager {
    /**
     * Initialize the Track Status Manager
     * @param {Object} options Configuration options
     */
    constructor(options = {}) {
        this.options = {
            activePollingInterval: 500,    // Polling interval for active tracks (500ms)
            idlePollingInterval: 5000,     // Polling interval when idle (5s)
            refreshThreshold: 3000,        // Minimum page refresh interval (3s)
            ...options
        };

        this.statusUpdater = null;
        this.initialized = false;
        this.trackRows = [];
        this.activeTracksCount = 0;
        
        // Toast system
        this.toastContainer = null;
        this.toastTimeout = null;
    }

    /**
     * Initialize the manager and set up all event listeners and track monitoring
     */
    init() {
        if (this.initialized) return;
        
        // Find all track rows
        this.trackRows = Array.from(document.querySelectorAll('[data-track-id]'));
        
        if (this.trackRows.length === 0) {
            console.log('No tracks found on page');
            return;
        }
        
        console.log(`Initializing track status manager with ${this.trackRows.length} tracks`);
        
        // Initialize the status updater
        this.statusUpdater = new TrackStatusAPI({
            interval: this.options.activePollingInterval,
            autoReload: false,  // We'll handle page refresh manually
            useBulk: true,
            onUpdate: tracks => this.handleTracksUpdate(tracks)
        });
        
        // Register all tracks for monitoring
        this.registerAllTracks();
        
        // Set up UI controls
        this.setupUIControls();
        
        // Initialize toast container
        this.initToastSystem();
        
        // Start the updater
        this.statusUpdater.start();
        
        // Set initialized flag
        this.initialized = true;
        
        console.log('Track Status Manager initialized successfully');
    }
    
    /**
     * Initialize the toast notification system
     */
    initToastSystem() {
        // Look for existing toast container
        this.toastContainer = document.getElementById('toast');
        
        // Create toast container if it doesn't exist
        if (!this.toastContainer) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.id = 'toast';
            this.toastContainer.className = 'fixed bottom-4 right-4 p-4 rounded shadow-lg transform transition-transform duration-300 ease-in-out translate-y-full hidden';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert';
            
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            svg.setAttribute('fill', 'none');
            svg.setAttribute('viewBox', '0 0 24 24');
            svg.setAttribute('class', 'stroke-info shrink-0 w-6 h-6');
            
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');
            path.setAttribute('stroke-width', '2');
            path.setAttribute('d', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z');
            
            svg.appendChild(path);
            alertDiv.appendChild(svg);
            
            const span = document.createElement('span');
            span.id = 'toast-message';
            alertDiv.appendChild(span);
            
            this.toastContainer.appendChild(alertDiv);
            document.body.appendChild(this.toastContainer);
        }
        
        // Make toast function globally available
        window.showToast = this.showToast.bind(this);
    }
    
    /**
     * Show a toast notification
     * @param {string} message - Message to display 
     * @param {string} type - Type of notification (info, success, warning, error)
     */
    showToast(message, type = 'info') {
        const toast = this.toastContainer;
        const toastMessage = document.getElementById('toast-message');
        
        if (!toast || !toastMessage) return;
        
        // Set the message and type
        toastMessage.textContent = message;
        toast.querySelector('.alert').className = `alert alert-${type}`;
        
        // Clear any existing timeout
        if (this.toastTimeout) {
            clearTimeout(this.toastTimeout);
        }
        
        // Show the toast
        toast.classList.remove('hidden', 'translate-y-full');
        toast.classList.add('translate-y-0');
        
        // Hide the toast after 3 seconds
        this.toastTimeout = setTimeout(() => {
            toast.classList.add('translate-y-full');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 300);
        }, 3000);
    }
    
    /**
     * Register all track rows for status monitoring
     */
    registerAllTracks() {
        this.trackRows.forEach(row => {
            const trackId = row.getAttribute('data-track-id');
            const statusCell = row.querySelector('.track-status');
            const progressCell = row.querySelector('.track-progress');
            
            if (statusCell && progressCell) {
                // Register with the status updater
                this.statusUpdater.watchTrack(trackId, {
                    status: statusCell,
                    progress: progressCell
                });
                
                // Add click handlers for action buttons
                this.attachActionHandlers(row, trackId);
            } else {
                console.warn(`Track ${trackId} missing status or progress cells`);
            }
        });
    }
    
    /**
     * Attach action button handlers to a track row
     * @param {HTMLElement} row - Track row element
     * @param {string} trackId - Track ID
     */
    attachActionHandlers(row, trackId) {
        // Start button
        const startBtn = row.querySelector('.start-track');
        if (startBtn) {
            startBtn.addEventListener('click', () => {
                this.handleTrackAction('start', trackId, startBtn);
            });
        }
        
        // Stop button
        const stopBtn = row.querySelector('.stop-track');
        if (stopBtn) {
            stopBtn.addEventListener('click', () => {
                this.handleTrackAction('stop', trackId, stopBtn);
            });
        }
        
        // Retry button
        const retryBtn = row.querySelector('.retry-track');
        if (retryBtn) {
            retryBtn.addEventListener('click', () => {
                this.handleTrackAction('retry', trackId, retryBtn);
            });
        }
        
        // Redownload button
        const redownloadBtn = row.querySelector('.redownload-track');
        if (redownloadBtn) {
            redownloadBtn.addEventListener('click', () => {
                this.handleTrackAction('redownload', trackId, redownloadBtn);
            });
        }
        
        // YouTube toggle button
        const youtubeToggleBtn = row.querySelector('.toggle-youtube-status');
        if (youtubeToggleBtn) {
            youtubeToggleBtn.addEventListener('click', () => {
                this.handleYoutubeToggle(trackId, youtubeToggleBtn);
            });
        }
    }
    
    /**
     * Handle YouTube toggle button click
     * @param {string} trackId - Track ID
     * @param {HTMLElement} button - Button element
     */
    async handleYoutubeToggle(trackId, button) {
        if (!trackId || !button) return;
        
        // Disable button and show loading state
        button.disabled = true;
        button.classList.add('loading');
        
        try {
            // Get the CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            // Send request to toggle YouTube status
            const response = await fetch(`/tracks/${trackId}/toggle-youtube-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            // Update the UI based on the new status
            const row = document.querySelector(`[data-track-id="${trackId}"]`);
            const youtubeCell = row.querySelector('.track-youtube');
            
            if (youtubeCell) {
                if (result.youtube_uploaded) {
                    // Update to show as uploaded
                    youtubeCell.innerHTML = `
                        <div class="flex items-center justify-center">
                            <a href="${result.youtube_url}" target="_blank" class="tooltip" data-tip="View on YouTube">
                                <span class="badge badge-sm badge-success">Uploaded</span>
                            </a>
                            <button type="button" class="ml-2 btn btn-xs btn-circle btn-ghost tooltip toggle-youtube-status" data-tip="Mark as not uploaded">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    `;
                } else {
                    // Update to show as not uploaded
                    youtubeCell.innerHTML = `
                        <button type="button" class="btn btn-xs btn-outline btn-primary toggle-youtube-status tooltip" data-tip="Mark as uploaded to YouTube">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            YouTube
                        </button>
                    `;
                }
                
                // Re-attach the event listener to the new button
                const newToggleBtn = youtubeCell.querySelector('.toggle-youtube-status');
                if (newToggleBtn) {
                    newToggleBtn.addEventListener('click', () => {
                        this.handleYoutubeToggle(trackId, newToggleBtn);
                    });
                }
            }
            
            // Show success toast
            this.showToast(result.message || 'YouTube status updated successfully', 'success');
            
        } catch (error) {
            console.error('Error toggling YouTube status:', error);
            this.showToast('Failed to update YouTube status: ' + error.message, 'error');
        } finally {
            // Reset button state
            button.disabled = false;
            button.classList.remove('loading');
        }
    }
    
    /**
     * Set up UI control elements
     */
    setupUIControls() {
        // Manual refresh button
        const refreshNowEl = document.getElementById('refresh-now');
        if (refreshNowEl) {
            refreshNowEl.addEventListener('click', () => {
                console.log('Manual page refresh requested');
                window.location.reload();
            });
        }
        
        // Bulk action buttons
        const startAllBtn = document.getElementById('start-all-tracks');
        if (startAllBtn) {
            startAllBtn.addEventListener('click', () => this.handleBulkAction('startAll', startAllBtn));
        }
        
        const stopAllBtn = document.getElementById('stop-all-tracks');
        if (stopAllBtn) {
            stopAllBtn.addEventListener('click', () => this.handleBulkAction('stopAll', stopAllBtn));
        }
        
        const retryAllBtn = document.getElementById('retry-all-tracks');
        if (retryAllBtn) {
            retryAllBtn.addEventListener('click', () => this.handleBulkAction('retryAll', retryAllBtn));
        }
    }
    
    /**
     * Handle track status updates
     * @param {Array} tracks - Array of updated track objects
     */
    handleTracksUpdate(tracks) {
        console.log(`Track status update received for ${tracks.length} tracks`);
        
        // Count tracks by status
        let pendingCount = 0;
        let processingCount = 0;
        
        // Check each track to find pending/processing ones
        tracks.forEach(track => {
            if (track.status === 'pending') pendingCount++;
            if (track.status === 'processing') processingCount++;
        });
        
        // Update active tracks count
        this.activeTracksCount = pendingCount + processingCount;
        
        // Update polling interval based on active tracks
        if (this.activeTracksCount > 0) {
            // Fast polling when tracks are active
            if (this.statusUpdater.options.interval !== this.options.activePollingInterval) {
                console.log(`Setting polling interval to ${this.options.activePollingInterval}ms (${this.activeTracksCount} active tracks)`);
                this.statusUpdater.options.interval = this.options.activePollingInterval;
            }
        } else {
            // Slow polling when no tracks are active
            if (this.statusUpdater.options.interval !== this.options.idlePollingInterval) {
                console.log(`Setting polling interval to ${this.options.idlePollingInterval}ms (no active tracks)`);
                this.statusUpdater.options.interval = this.options.idlePollingInterval;
            }
        }
        
        // Update status counts in UI
        this.updateStatusCounts();
    }
    
    /**
     * Consider if a page refresh is needed and schedule it if appropriate
     */
    considerPageRefresh() {
        return;
    }
    
    /**
     * Update status counts in the UI
     */
    updateStatusCounts() {
        // Get current counts from the API module
        const counts = this.statusUpdater.countTracksByStatus();
        
        // Update count displays
        document.querySelectorAll('[data-count]').forEach(el => {
            const countType = el.dataset.count;
            if (counts[countType] !== undefined) {
                el.textContent = counts[countType];
            }
        });
    }
    
    /**
     * Handle individual track actions
     * @param {string} action - Action to perform (start, stop, retry, redownload)
     * @param {string} trackId - Track ID
     * @param {HTMLElement} button - Button element that triggered the action
     */
    async handleTrackAction(action, trackId, button) {
        if (!trackId || !button) return;
        
        try {
            button.classList.add('loading');
            let result = null;
            
            switch (action) {
                case 'start':
                    result = await TrackStatusAPI.startTrack(trackId);
                    if (result.success) {
                        this.showToast('Track processing started', 'success');
                    }
                    break;
                    
                case 'stop':
                    result = await TrackStatusAPI.stopTrack(trackId);
                    if (result.success) {
                        this.showToast('Track processing stopped', 'warning');
                    }
                    break;
                    
                case 'retry':
                    result = await TrackStatusAPI.retryTrack(trackId);
                    if (result.success) {
                        this.showToast('Track processing retried', 'success');
                    }
                    break;
                    
                case 'redownload':
                    // Confirm action
                    if (!confirm("This will redownload the track's files and process it again. Continue?")) {
                        button.classList.remove('loading');
                        return;
                    }
                    
                    const response = await fetch(`/api/tracks/${trackId}/start`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        },
                        body: JSON.stringify({ force_redownload: true })
                    });
                    
                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Failed to redownload track');
                    }
                    
                    result = await response.json();
                    if (result.success) {
                        this.showToast('Track redownload started', 'success');
                    }
                    break;
                    
                default:
                    console.error(`Unknown track action: ${action}`);
                    return;
            }
            
            // Force immediate status update after action
            this.statusUpdater.updateTrackStatus();
            
        } catch (error) {
            console.error(`Failed to ${action} track:`, error);
            this.showToast(`Failed to ${action} track: ${error.message}`, 'error');
        } finally {
            button.classList.remove('loading');
        }
    }
    
    /**
     * Handle bulk track actions
     * @param {string} action - Action to perform (startAll, stopAll, retryAll)
     * @param {HTMLElement} button - Button element that triggered the action
     */
    async handleBulkAction(action, button) {
        if (!button) return;
        
        try {
            // Confirm action
            let confirmMessage = '';
            switch (action) {
                case 'startAll':
                    confirmMessage = 'Start processing all pending and failed tracks?';
                    break;
                case 'stopAll':
                    confirmMessage = 'Stop processing all active tracks?';
                    break;
                case 'retryAll':
                    confirmMessage = 'Retry all failed tracks?';
                    break;
                default:
                    console.error(`Unknown bulk action: ${action}`);
                    return;
            }
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            button.classList.add('loading');
            let result = null;
            
            switch (action) {
                case 'startAll':
                    result = await TrackStatusAPI.startAllTracks();
                    if (result.success) {
                        this.showToast(`Started processing ${result.count || 0} tracks`, 'success');
                    }
                    break;
                    
                case 'stopAll':
                    result = await TrackStatusAPI.stopAllTracks();
                    if (result.success) {
                        this.showToast(`Stopped processing ${result.count || 0} tracks`, 'warning');
                    }
                    break;
                    
                case 'retryAll':
                    result = await TrackStatusAPI.retryAllTracks();
                    if (result.success) {
                        this.showToast(`Retried ${result.count || 0} failed tracks`, 'success');
                    }
                    break;
            }
            
            // Force immediate status update after bulk action
            this.statusUpdater.updateTrackStatus();
            
            // Refresh page after bulk actions to ensure consistent UI
            setTimeout(() => {
                window.location.reload();
            }, 1000);
            
        } catch (error) {
            console.error(`Failed to perform bulk action ${action}:`, error);
            this.showToast(`Failed to perform action: ${error.message}`, 'error');
        } finally {
            button.classList.remove('loading');
        }
    }
} 