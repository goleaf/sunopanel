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
     */
    constructor(options = {}) {
        this.options = {
            interval: 3000,
            useBulk: true,
            ...options
        };
        
        this.tracks = new Map();
        this.updateTimer = null;
        this.isUpdating = false;
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
        
        return this;
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
            if (this.options.useBulk && this.tracks.size > 1) {
                // Use bulk API for multiple tracks
                await this.updateTracksBulk();
            } else {
                // Update tracks individually
                for (const [_, track] of this.tracks) {
                    if (['processing', 'pending'].includes(track.status)) {
                        await this.updateSingleTrack(track);
                    }
                }
            }
            
            // Call the onUpdate callback if provided
            if (typeof this.options.onUpdate === 'function') {
                this.options.onUpdate(Array.from(this.tracks.values()));
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
            // Get IDs of all tracks in processing or pending state
            const trackIds = Array.from(this.tracks.values())
                .filter(track => ['processing', 'pending'].includes(track.status))
                .map(track => track.id);
            
            if (trackIds.length === 0) return;
            
            const response = await fetch('/api/tracks/status-bulk', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ ids: trackIds })
            });
            
            if (!response.ok) {
                throw new Error(`Error fetching bulk track status: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            // Update each track's UI with the received data
            for (const trackData of data.tracks) {
                const track = this.tracks.get(trackData.id.toString());
                if (track) {
                    this.updateTrackUI(track, trackData);
                }
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
        // Skip if nothing changed
        if (track.status === data.status && parseInt(track.progress) === parseInt(data.progress)) {
            return;
        }
        
        // Update internal state
        track.status = data.status;
        track.progress = data.progress;
        
        // Status element update
        if (track.elements.status) {
            track.elements.status.dataset.status = data.status;
            
            // Generate status HTML based on the status
            let statusHTML = '';
            if (data.status === 'completed') {
                statusHTML = `<span class="badge badge-success">Completed</span>`;
            } else if (data.status === 'processing') {
                statusHTML = `<span class="badge badge-warning">Processing</span>`;
            } else if (data.status === 'failed') {
                statusHTML = `<span class="badge badge-error">Failed</span>`;
            } else {
                statusHTML = `<span class="badge badge-info">Pending</span>`;
            }
            
            track.elements.status.innerHTML = statusHTML;
        }
        
        // Progress element update
        if (track.elements.progress) {
            track.elements.progress.dataset.progress = data.progress;
            
            // Generate progress HTML based on the status
            let progressHTML = '';
            if (data.status === 'processing') {
                progressHTML = `
                    <div class="flex items-center">
                        <progress class="progress progress-xs progress-warning flex-grow mr-1" value="${data.progress}" max="100"></progress>
                        <span class="text-xs">${data.progress}%</span>
                    </div>
                `;
            } else if (data.status === 'completed') {
                progressHTML = `
                    <div class="flex items-center">
                        <progress class="progress progress-xs progress-success flex-grow mr-1" value="100" max="100"></progress>
                        <span class="text-xs">100%</span>
                    </div>
                `;
            } else if (data.status === 'failed') {
                progressHTML = `
                    <div class="tooltip w-full" data-tip="${data.error_message || 'Unknown error'}">
                        <progress class="progress progress-xs progress-error w-full" value="100" max="100"></progress>
                    </div>
                `;
            } else {
                progressHTML = `
                    <div class="flex items-center">
                        <progress class="progress progress-xs progress-info flex-grow mr-1" value="0" max="100"></progress>
                        <span class="text-xs">0%</span>
                    </div>
                `;
            }
            
            track.elements.progress.innerHTML = progressHTML;
        }
        
        // Page reload logic for completed or failed tracks
        if (track.elements.reload && (data.status === 'completed' || data.status === 'failed')) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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
} 