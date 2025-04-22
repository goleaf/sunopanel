/**
 * Tracks Page JavaScript
 * Entry point for track listing pages
 */

import './bootstrap';
import TrackStatusManager from './modules/trackStatusManager';

// Initialize when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on a track listing page by looking for track rows
    const trackRows = document.querySelectorAll('[data-track-id]');
    
    if (trackRows.length > 0) {
        // Initialize track status manager
        console.log(`Initializing track status manager (found ${trackRows.length} tracks)`);
        
        window.trackManager = new TrackStatusManager({
            activePollingInterval: 500,   // 500ms when tracks are active
            idlePollingInterval: 3000,    // 3s when no tracks are active
            refreshThreshold: 3000        // Minimum time between page refreshes
        });
        
        // Initialize the manager
        window.trackManager.init();
    } else {
        console.log('No tracks found on page, skipping track status initialization');
    }
}); 