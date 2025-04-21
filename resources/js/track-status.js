/**
 * Track Status API Module
 * 
 * This module provides functions for interacting with the track status API
 * and efficiently updating track statuses on different pages.
 */

export default class TrackStatusAPI {
    /**
     * Initialize the track status updater
     * 
     * @param {Object} options - Configuration options
     * @param {Number} options.interval - Update interval in ms (default: 3000)
     * @param {Function} options.onUpdate - Callback when status is updated (optional)
     * @param {Boolean} options.useBulk - Whether to use bulk API for multiple tracks (default: true)
     * @param {Boolean} options.autoReload - Auto-reload the page after a certain time
     * @param {Number} options.reloadInterval - How often to reload the page (30 seconds)
     * @param {Boolean} options.hideCompleted - Hide completed tracks
     */
    constructor(options = {}) {
        this.options = {
            interval: 3000,
            useBulk: true,
            autoReload: true,          // Auto-reload the page after a certain time
            reloadInterval: 30000,     // How often to reload the page (30 seconds)
            hideCompleted: false,      // Hide completed tracks
            ...options
        };
        
        this.tracks = new Map();
        this.updateTimer = null;
        this.reloadTimer = null;
        this.isUpdating = false;
        this.lastReloadTime = new Date().getTime();
        
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
            total: 0,
            completed: 0,
            processing: 0,
            pending: 0,
            failed: 0,
            stopped: 0
        };
        
        for (const [_, track] of this.tracks) {
            counts.total++;
            if (track.status) {
                counts[track.status] = (counts[track.status] || 0) + 1;
            }
        }
        
        return counts;
    }
    
    /**
     * Register a track to be watched for status updates
     * 
     * @param {Number} trackId - The track ID
     * @param {Object} elements - DOM elements to update
     * @param {HTMLElement} elements.status - Element for status display
     * @param {HTMLElement} elements.progress - Element for progress display
     * @param {Boolean} elements.reload - Whether to reload page on completion (default: false)
     * @returns {TrackStatusAPI} - Returns this for method chaining
     */
    watchTrack(trackId, elements) {
        this.tracks.set(trackId.toString(), {
            id: trackId,
            elements,
            status: elements.status?.dataset?.status || null,
            progress: elements.progress?.dataset?.progress || 0,
        });
        
        // Add track-row class to the track row for animation targeting
        const row = document.querySelector(`[data-track-id="${trackId}"]`);
        if (row) {
            row.classList.add('track-row');
        }
        
        return this;
    }
    
    /**
     * Start watching all registered tracks
     * 
     * @returns {TrackStatusAPI} - Returns this for method chaining
     */
    start() {
        if (this.tracks.size === 0) {
            console.warn('No tracks registered for status updates');
            return this;
        }
        
        // Do an immediate update
        this.updateTrackStatus();
        
        // Set up interval for future updates
        this.updateTimer = setInterval(() => {
            this.updateTrackStatus();
        }, this.options.interval);
        
        // Set up auto-reload if enabled
        if (this.options.autoReload) {
            console.log(`Auto-reload enabled, will refresh every ${this.options.reloadInterval/1000} seconds`);
            this.reloadTimer = setInterval(() => {
                // Only reload if we have completed or failed tracks or if it's been more than 2 minutes
                const needsReload = this.checkIfReloadNeeded();
                if (needsReload) {
                    console.log('Auto-reloading page to refresh tracks list');
                    window.location.reload();
                }
            }, this.options.reloadInterval);
        }
        
        return this;
    }
    
    /**
     * Stop watching all tracks
     * 
     * @returns {TrackStatusAPI} - Returns this for method chaining
     */
    stop() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
        
        if (this.reloadTimer) {
            clearInterval(this.reloadTimer);
            this.reloadTimer = null;
        }
        
        return this;
    }
    
    /**
     * Check if the page needs to be reloaded
     * 
     * @returns {boolean} - Whether a reload is needed
     */
    checkIfReloadNeeded() {
        // Check if any tracks have completed or failed recently
        let completedOrFailed = false;
        
        for (const [_, track] of this.tracks) {
            if (['completed', 'failed'].includes(track.status)) {
                completedOrFailed = true;
                break;
            }
        }
        
        // Also reload if it's been more than 2 minutes since the last reload
        const currentTime = new Date().getTime();
        const timeSinceLastReload = currentTime - this.lastReloadTime;
        const forceReload = timeSinceLastReload > 120000; // 2 minutes
        
        return completedOrFailed || forceReload;
    }
    
    /**
     * Update track status for all watched tracks
     * 
     * @private
     */
    async updateTrackStatus() {
        if (this.isUpdating || this.tracks.size === 0) return;
        
        this.isUpdating = true;
        
        try {
            // Force update interval to be no less than 1 second
            const startTime = new Date().getTime();
            
            if (this.options.useBulk && this.tracks.size > 1) {
                // Use bulk API for multiple tracks
                await this.updateTracksBulk();
            } else {
                // Update tracks individually
                for (const [_, track] of this.tracks) {
                    // Update all tracks regardless of status
                    await this.updateSingleTrack(track);
                }
            }
            
            // Call the onUpdate callback if provided
            if (typeof this.options.onUpdate === 'function') {
                this.options.onUpdate(Array.from(this.tracks.values()));
            }
            
            // Ensure minimum update interval to prevent too frequent updates
            const elapsedTime = new Date().getTime() - startTime;
            if (elapsedTime < 1000) {
                await new Promise(resolve => setTimeout(resolve, 1000 - elapsedTime));
            }
        } catch (error) {
            console.error('Error updating track status:', error);
        } finally {
            this.isUpdating = false;
        }
    }
    
    /**
     * Update a single track's status
     * 
     * @private
     * @param {Object} track - Track to update
     */
    async updateSingleTrack(track) {
        try {
            const response = await fetch(`/api/tracks/${track.id}/status`);
            
            if (!response.ok) {
                throw new Error(`Error fetching track status: ${response.statusText}`);
            }
            
            const data = await response.json();
            this.updateTrackUI(track, data);
        } catch (error) {
            console.error(`Error updating track ${track.id}:`, error);
        }
    }
    
    /**
     * Update multiple tracks at once using the bulk API
     * 
     * @private
     */
    async updateTracksBulk() {
        try {
            // Get IDs of all tracks, not just processing or pending
            const trackIds = Array.from(this.tracks.values())
                .map(track => track.id);
            
            console.log('Tracks to update:', trackIds);
            
            if (trackIds.length === 0) {
                console.log('No tracks to update');
                return;
            }
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                console.error('CSRF token not found');
                return;
            }
            
            console.log('Sending bulk status request for tracks:', trackIds);
            
            const response = await fetch('/api/tracks/status-bulk', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ ids: trackIds })
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Bulk status response error:', response.status, errorText);
                throw new Error(`Error fetching bulk track status: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Bulk status response:', data);
            
            // Update each track's UI with the received data
            if (data.tracks && Array.isArray(data.tracks)) {
                for (const trackData of data.tracks) {
                    const track = this.tracks.get(trackData.id.toString());
                    if (track) {
                        console.log(`Updating track ${trackData.id} UI:`, trackData);
                        this.updateTrackUI(track, trackData);
                    }
                }
            } else {
                console.error('Invalid response format:', data);
            }
        } catch (error) {
            console.error('Error updating tracks in bulk:', error);
        }
    }
    
    /**
     * Update a track's UI elements based on status data
     * 
     * @private
     * @param {Object} track - Track object with elements
     * @param {Object} data - Status data from API
     */
    updateTrackUI(track, data) {
        // Log current status vs new status for debugging
        console.log(`Track ${data.id} update: ${track.status} → ${data.status} (${track.progress}% → ${data.progress}%)`);
        
        // Always update - don't skip even if status appears the same
        // This ensures any missed UI updates get applied
        
        // Find the row element
        const row = document.querySelector(`[data-track-id="${data.id}"]`);
        if (!row) {
            console.error(`Row for track ${data.id} not found`);
            return;
        }
        
        // Format the progress as a whole number
        const currentProgress = parseInt(track.progress || 0);
        const newProgress = parseInt(data.progress || 0);
        
        // Check if status or progress actually changed
        const statusChanged = track.status !== data.status;
        const progressChanged = currentProgress !== newProgress;
        
        // Update internal state
        track.status = data.status;
        track.progress = data.progress;
        
        // Update row status classes
        row.classList.remove('status-completed', 'status-failed', 'status-processing', 'status-pending', 'status-stopped');
        row.classList.add(`status-${data.status}`);
        
        // Apply visibility filters
        if (!this.visibilityFilters[data.status]) {
            row.style.display = 'none';
        } else {
            row.style.display = '';
        }
        
        // Add update animation to the row if something changed
        if (statusChanged || progressChanged) {
            // Mark the row as having updates
            row.classList.remove('row-updated');
            // Force a reflow to restart the animation
            void row.offsetWidth;
            row.classList.add('row-updated');
            // Remove it after the animation completes
            setTimeout(() => {
                row.classList.remove('row-updated');
            }, 1000);
        }
        
        // Status element update
        if (track.elements.status) {
            track.elements.status.dataset.status = data.status;
            
            // Generate status HTML based on the status
            let statusHTML = '';
            if (data.status === 'completed') {
                statusHTML = `<span class="badge badge-sm badge-success">Completed</span>`;
            } else if (data.status === 'processing') {
                statusHTML = `<span class="badge badge-sm badge-warning">Processing</span>`;
            } else if (data.status === 'failed') {
                statusHTML = `<span class="badge badge-sm badge-error">Failed</span>`;
            } else if (data.status === 'stopped') {
                statusHTML = `<span class="badge badge-sm badge-warning">Stopped</span>`;
            } else {
                statusHTML = `<span class="badge badge-sm badge-info">Pending</span>`;
            }
            
            // Force update of status element
            track.elements.status.innerHTML = statusHTML;
            
            // Add blinking effect to indicate update if status changed
            if (statusChanged) {
                track.elements.status.classList.remove('status-updated');
                // Force a reflow to restart the animation
                void track.elements.status.offsetWidth;
                track.elements.status.classList.add('status-updated');
                setTimeout(() => {
                    track.elements.status.classList.remove('status-updated');
                }, 500);
            }
        }
        
        // Progress element update
        if (track.elements.progress) {
            track.elements.progress.dataset.progress = data.progress;
            
            // Generate progress HTML based on the status
            let progressHTML = '';
            if (data.status === 'processing') {
                progressHTML = `
                    <div class="flex items-center">
                        <progress class="progress progress-xs progress-warning flex-grow mr-1" value="${newProgress}" max="100"></progress>
                        <span class="text-xs progress-percentage">${newProgress}%</span>
                    </div>
                `;
                
                // Update the HTML first (if it doesn't exist yet)
                const existingProgressBar = track.elements.progress.querySelector('progress');
                if (!existingProgressBar) {
                    track.elements.progress.innerHTML = progressHTML;
                } else {
                    // Update existing progress bar value
                    existingProgressBar.value = newProgress;
                    
                    // Animate the percentage text if it changed significantly
                    if (progressChanged && Math.abs(newProgress - currentProgress) >= 1) {
                        const percentEl = track.elements.progress.querySelector('.progress-percentage');
                        if (percentEl && window.trackAnimations) {
                            window.trackAnimations.animateNumber(percentEl, currentProgress, newProgress);
                        } else {
                            // Fallback if animation helper not available
                            percentEl.textContent = `${newProgress}%`;
                            percentEl.classList.add('percentage-updated');
                            setTimeout(() => {
                                percentEl.classList.remove('percentage-updated');
                            }, 800);
                        }
                    }
                }
            } else if (data.status === 'completed') {
                progressHTML = `
                    <div class="flex items-center">
                        <progress class="progress progress-xs progress-success flex-grow mr-1" value="100" max="100"></progress>
                        <span class="text-xs progress-percentage">100%</span>
                    </div>
                `;
                track.elements.progress.innerHTML = progressHTML;
            } else if (data.status === 'failed') {
                progressHTML = `
                    <div class="tooltip w-full" data-tip="${data.error_message || 'Unknown error'}">
                        <progress class="progress progress-xs progress-error w-full" value="100" max="100"></progress>
                    </div>
                `;
                track.elements.progress.innerHTML = progressHTML;
            } else if (data.status === 'stopped') {
                progressHTML = `
                    <div class="tooltip w-full" data-tip="Processing was manually stopped">
                        <progress class="progress progress-xs progress-warning w-full" value="${newProgress}" max="100"></progress>
                    </div>
                `;
                track.elements.progress.innerHTML = progressHTML;
            } else {
                progressHTML = `
                    <div class="flex items-center">
                        <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                        <span class="text-xs progress-percentage">0%</span>
                    </div>
                `;
                track.elements.progress.innerHTML = progressHTML;
            }
        }
        
        // Update the action buttons based on new status
        try {
            this.updateTrackActionButtons(row, data.status);
        } catch(err) {
            console.error('Error updating action buttons:', err);
        }
    }
    
    /**
     * Update the action buttons for a track based on status
     * 
     * @param {HTMLElement} row - The track row element
     * @param {string} newStatus - The new status
     */
    updateTrackActionButtons(row, newStatus) {
        const actionsCell = row.querySelector('td:last-child > div');
        if (!actionsCell) {
            console.error('Actions cell not found');
            return;
        }
        
        const trackId = row.getAttribute('data-track-id');
        
        // Clear existing action buttons (except View and Delete)
        const buttons = actionsCell.querySelectorAll('button:not([type="submit"])');
        buttons.forEach(button => button.remove());
        
        // Add the appropriate buttons based on the new status
        if (['failed', 'stopped'].includes(newStatus)) {
            // Add start button
            const startButton = document.createElement('button');
            startButton.className = 'btn btn-sm btn-circle btn-success start-track';
            startButton.setAttribute('data-track-id', trackId);
            startButton.setAttribute('title', 'Start Processing');
            startButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                </svg>
            `;
            
            // Insert after view button
            const viewButton = actionsCell.querySelector('a');
            if (viewButton) {
                viewButton.after(startButton);
                
                // Add event listener
                startButton.addEventListener('click', this.handleStartClick.bind(this));
            }
            
            // Add retry button if failed
            if (newStatus === 'failed') {
                const retryButton = document.createElement('button');
                retryButton.className = 'btn btn-sm btn-circle btn-warning retry-track';
                retryButton.setAttribute('data-track-id', trackId);
                retryButton.setAttribute('title', 'Retry Processing');
                retryButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                `;
                startButton.after(retryButton);
                
                // Add event listener
                retryButton.addEventListener('click', this.handleRetryClick.bind(this));
            }
        } else if (['processing', 'pending'].includes(newStatus)) {
            // Add stop button
            const stopButton = document.createElement('button');
            stopButton.className = 'btn btn-sm btn-circle btn-error stop-track';
            stopButton.setAttribute('data-track-id', trackId);
            stopButton.setAttribute('title', 'Stop Processing');
            stopButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18h12a1 1 0 001-1V7a1 1 0 00-1-1H6a1 1 0 00-1 1v10a1 1 0 001 1z" />
                </svg>
            `;
            
            // Insert after view button
            const viewButton = actionsCell.querySelector('a');
            if (viewButton) {
                viewButton.after(stopButton);
                
                // Add event listener
                stopButton.addEventListener('click', this.handleStopClick.bind(this));
            }
        } else if (newStatus === 'completed') {
            // Add redownload button for completed tracks
            const redownloadButton = document.createElement('button');
            redownloadButton.className = 'btn btn-sm btn-circle btn-warning redownload-track';
            redownloadButton.setAttribute('data-track-id', trackId);
            redownloadButton.setAttribute('title', 'Redownload and Process Again');
            redownloadButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
            `;
            
            // Insert after view button
            const viewButton = actionsCell.querySelector('a');
            if (viewButton) {
                viewButton.after(redownloadButton);
                
                // Add event listener
                redownloadButton.addEventListener('click', this.handleRedownloadClick.bind(this));
            }
        }
    }
    
    /**
     * Handle start button click
     * 
     * @param {Event} event
     */
    async handleStartClick(event) {
        const button = event.currentTarget;
        const trackId = button.getAttribute('data-track-id');
        
        try {
            button.classList.add('loading');
            const result = await TrackStatusAPI.startTrack(trackId);
            
            if (result.success) {
                const row = document.querySelector(`[data-track-id="${trackId}"]`);
                const statusCell = row.querySelector('.track-status');
                const progressCell = row.querySelector('.track-progress');
                
                statusCell.innerHTML = '<span class="badge badge-sm badge-info">Pending</span>';
                progressCell.innerHTML = `
                    <div class="flex items-center">
                        <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                        <span class="text-xs progress-percentage">0%</span>
                    </div>
                `;
                
                // Update buttons
                this.updateTrackActionButtons(row, 'pending');
                
                if (window.showToast) {
                    window.showToast('Track processing started', 'success');
                }
            }
        } catch (error) {
            console.error('Failed to start processing:', error);
            if (window.showToast) {
                window.showToast('Failed to start processing: ' + error.message, 'error');
            }
        } finally {
            button.classList.remove('loading');
        }
    }
    
    /**
     * Handle stop button click
     * 
     * @param {Event} event
     */
    async handleStopClick(event) {
        const button = event.currentTarget;
        const trackId = button.getAttribute('data-track-id');
        
        try {
            button.classList.add('loading');
            const result = await TrackStatusAPI.stopTrack(trackId);
            
            if (result.success) {
                const row = document.querySelector(`[data-track-id="${trackId}"]`);
                const statusCell = row.querySelector('.track-status');
                const progressCell = row.querySelector('.track-progress');
                const currentProgress = progressCell.dataset.progress || 0;
                
                statusCell.innerHTML = '<span class="badge badge-sm badge-warning">Stopped</span>';
                progressCell.innerHTML = `
                    <div class="tooltip w-full" data-tip="Processing was manually stopped">
                        <progress class="progress progress-xs progress-warning w-full" value="${currentProgress}" max="100"></progress>
                    </div>
                `;
                
                // Update buttons
                this.updateTrackActionButtons(row, 'stopped');
                
                if (window.showToast) {
                    window.showToast('Track processing stopped', 'warning');
                }
            }
        } catch (error) {
            console.error('Failed to stop processing:', error);
            if (window.showToast) {
                window.showToast('Failed to stop processing: ' + error.message, 'error');
            }
        } finally {
            button.classList.remove('loading');
        }
    }
    
    /**
     * Handle retry button click
     * 
     * @param {Event} event
     */
    async handleRetryClick(event) {
        const button = event.currentTarget;
        const trackId = button.getAttribute('data-track-id');
        
        try {
            button.classList.add('loading');
            const result = await TrackStatusAPI.retryTrack(trackId);
            
            if (result.success) {
                const row = document.querySelector(`[data-track-id="${trackId}"]`);
                const statusCell = row.querySelector('.track-status');
                const progressCell = row.querySelector('.track-progress');
                
                statusCell.innerHTML = '<span class="badge badge-sm badge-info">Pending</span>';
                progressCell.innerHTML = `
                    <div class="flex items-center">
                        <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                        <span class="text-xs progress-percentage">0%</span>
                    </div>
                `;
                
                // Update buttons
                this.updateTrackActionButtons(row, 'pending');
                
                if (window.showToast) {
                    window.showToast('Track processing retried', 'success');
                }
            }
        } catch (error) {
            console.error('Failed to retry processing:', error);
            if (window.showToast) {
                window.showToast('Failed to retry processing: ' + error.message, 'error');
            }
        } finally {
            button.classList.remove('loading');
        }
    }
    
    /**
     * Handle redownload button click
     * 
     * @param {Event} event
     */
    async handleRedownloadClick(event) {
        const button = event.currentTarget;
        const trackId = button.getAttribute('data-track-id');
        
        try {
            button.classList.add('loading');
            
            // Confirm action
            if (!confirm("This will redownload the track's files and process it again. Continue?")) {
                button.classList.remove('loading');
                return;
            }
            
            // Call the start route which will handle redownload
            const response = await fetch(`/api/tracks/${trackId}/start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                // Send force_redownload flag
                body: JSON.stringify({ force_redownload: true })
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to redownload track');
            }
            
            const result = await response.json();
            
            if (result.success) {
                const row = document.querySelector(`[data-track-id="${trackId}"]`);
                const statusCell = row.querySelector('.track-status');
                const progressCell = row.querySelector('.track-progress');
                
                statusCell.innerHTML = '<span class="badge badge-sm badge-info">Pending</span>';
                progressCell.innerHTML = `
                    <div class="flex items-center">
                        <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                        <span class="text-xs progress-percentage">0%</span>
                    </div>
                `;
                
                // Update buttons
                this.updateTrackActionButtons(row, 'pending');
                
                if (window.showToast) {
                    window.showToast('Track redownload started', 'success');
                }
            }
        } catch (error) {
            console.error('Failed to redownload track:', error);
            if (window.showToast) {
                window.showToast('Failed to redownload track: ' + error.message, 'error');
            }
        } finally {
            button.classList.remove('loading');
        }
    }
    
    /**
     * Start processing a track
     * 
     * @param {Number} trackId - The track ID
     * @returns {Promise} - Promise resolving to API response
     */
    static async startTrack(trackId) {
        try {
            const response = await fetch(`/api/tracks/${trackId}/start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to start track processing');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error starting track processing:', error);
            throw error;
        }
    }
    
    /**
     * Stop processing a track
     * 
     * @param {Number} trackId - The track ID
     * @returns {Promise} - Promise resolving to API response
     */
    static async stopTrack(trackId) {
        try {
            const response = await fetch(`/api/tracks/${trackId}/stop`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to stop track processing');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error stopping track processing:', error);
            throw error;
        }
    }
    
    /**
     * Start processing all tracks
     * 
     * @returns {Promise} - Promise resolving to API response
     */
    static async startAllTracks() {
        try {
            const response = await fetch('/api/tracks/start-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to start all tracks');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error starting all tracks:', error);
            throw error;
        }
    }
    
    /**
     * Stop processing all tracks
     * 
     * @returns {Promise} - Promise resolving to API response
     */
    static async stopAllTracks() {
        try {
            const response = await fetch('/api/tracks/stop-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to stop all tracks');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error stopping all tracks:', error);
            throw error;
        }
    }
    
    /**
     * Retry processing a failed track
     * 
     * @param {Number} trackId - The track ID 
     * @returns {Promise} - Promise resolving to API response
     */
    static async retryTrack(trackId) {
        try {
            const response = await fetch(`/api/tracks/${trackId}/retry`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to retry track');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error retrying track:', error);
            throw error;
        }
    }
    
    /**
     * Retry all failed tracks
     * 
     * @returns {Promise} - Promise resolving to API response
     */
    static async retryAllFailed() {
        try {
            const response = await fetch('/api/tracks/retry-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to retry all tracks');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error retrying all tracks:', error);
            throw error;
        }
    }
    
    /**
     * Handle start all tracks button click
     * 
     * @private
     * @param {Event} event
     */
    async handleStartAllClick(event) {
        const button = event.currentTarget;
        
        try {
            // Confirm action
            if (!confirm('Start processing all pending and failed tracks?')) {
                return;
            }
            
            button.classList.add('loading');
            const result = await TrackStatusAPI.startAllTracks();
            
            if (result.success) {
                // Update UI for all affected tracks
                if (result.affected_ids && Array.isArray(result.affected_ids)) {
                    result.affected_ids.forEach(trackId => {
                        const row = document.querySelector(`[data-track-id="${trackId}"]`);
                        if (row) {
                            const statusCell = row.querySelector('.track-status');
                            const progressCell = row.querySelector('.track-progress');
                            
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="badge badge-sm badge-info">Pending</span>';
                                statusCell.dataset.status = 'pending';
                            }
                            
                            if (progressCell) {
                                progressCell.innerHTML = `
                                    <div class="flex items-center">
                                        <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                                        <span class="text-xs progress-percentage">0%</span>
                                    </div>
                                `;
                                progressCell.dataset.progress = 0;
                            }
                            
                            // Update the row classes
                            row.classList.remove('status-completed', 'status-failed', 'status-processing', 'status-stopped');
                            row.classList.add('status-pending');
                            
                            // Update buttons
                            this.updateTrackActionButtons(row, 'pending');
                            
                            // Add animation
                            row.classList.remove('row-updated');
                            void row.offsetWidth;
                            row.classList.add('row-updated');
                        }
                    });
                }
                
                if (window.showToast) {
                    window.showToast(`Started processing ${result.count || 0} tracks`, 'success');
                }
            }
        } catch (error) {
            console.error('Failed to start all tracks:', error);
            if (window.showToast) {
                window.showToast('Failed to start all tracks: ' + error.message, 'error');
            }
        } finally {
            button.classList.remove('loading');
        }
    }
    
    /**
     * Handle stop all tracks button click
     * 
     * @private
     * @param {Event} event
     */
    async handleStopAllClick(event) {
        const button = event.currentTarget;
        
        try {
            // Confirm action
            if (!confirm('Stop processing all active tracks?')) {
                return;
            }
            
            button.classList.add('loading');
            const result = await TrackStatusAPI.stopAllTracks();
            
            if (result.success) {
                // Update UI for all affected tracks
                if (result.affected_ids && Array.isArray(result.affected_ids)) {
                    result.affected_ids.forEach(trackId => {
                        const row = document.querySelector(`[data-track-id="${trackId}"]`);
                        if (row) {
                            const statusCell = row.querySelector('.track-status');
                            const progressCell = row.querySelector('.track-progress');
                            const currentProgress = progressCell?.dataset?.progress || 0;
                            
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="badge badge-sm badge-warning">Stopped</span>';
                                statusCell.dataset.status = 'stopped';
                            }
                            
                            if (progressCell) {
                                progressCell.innerHTML = `
                                    <div class="tooltip w-full" data-tip="Processing was manually stopped">
                                        <progress class="progress progress-xs progress-warning w-full" value="${currentProgress}" max="100"></progress>
                                    </div>
                                `;
                            }
                            
                            // Update the row classes
                            row.classList.remove('status-completed', 'status-failed', 'status-processing', 'status-pending');
                            row.classList.add('status-stopped');
                            
                            // Update buttons
                            this.updateTrackActionButtons(row, 'stopped');
                            
                            // Add animation
                            row.classList.remove('row-updated');
                            void row.offsetWidth;
                            row.classList.add('row-updated');
                        }
                    });
                }
                
                if (window.showToast) {
                    window.showToast(`Stopped processing ${result.count || 0} tracks`, 'warning');
                }
            }
        } catch (error) {
            console.error('Failed to stop all tracks:', error);
            if (window.showToast) {
                window.showToast('Failed to stop all tracks: ' + error.message, 'error');
            }
        } finally {
            button.classList.remove('loading');
        }
    }
    
    /**
     * Handle retry all failed tracks button click
     * 
     * @private
     * @param {Event} event
     */
    async handleRetryAllClick(event) {
        const button = event.currentTarget;
        
        try {
            // Confirm action
            if (!confirm('Retry all failed tracks?')) {
                return;
            }
            
            button.classList.add('loading');
            const result = await TrackStatusAPI.retryAllFailed();
            
            if (result.success) {
                // Update UI for all affected tracks
                if (result.affected_ids && Array.isArray(result.affected_ids)) {
                    result.affected_ids.forEach(trackId => {
                        const row = document.querySelector(`[data-track-id="${trackId}"]`);
                        if (row) {
                            const statusCell = row.querySelector('.track-status');
                            const progressCell = row.querySelector('.track-progress');
                            
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="badge badge-sm badge-info">Pending</span>';
                                statusCell.dataset.status = 'pending';
                            }
                            
                            if (progressCell) {
                                progressCell.innerHTML = `
                                    <div class="flex items-center">
                                        <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                                        <span class="text-xs progress-percentage">0%</span>
                                    </div>
                                `;
                                progressCell.dataset.progress = 0;
                            }
                            
                            // Update the row classes
                            row.classList.remove('status-completed', 'status-failed', 'status-processing', 'status-stopped');
                            row.classList.add('status-pending');
                            
                            // Update buttons
                            this.updateTrackActionButtons(row, 'pending');
                            
                            // Add animation
                            row.classList.remove('row-updated');
                            void row.offsetWidth;
                            row.classList.add('row-updated');
                        }
                    });
                }
                
                if (window.showToast) {
                    window.showToast(`Retried ${result.count || 0} failed tracks`, 'success');
                }
            }
        } catch (error) {
            console.error('Failed to retry all failed tracks:', error);
            if (window.showToast) {
                window.showToast('Failed to retry all failed tracks: ' + error.message, 'error');
            }
        } finally {
            button.classList.remove('loading');
        }
    }
} 