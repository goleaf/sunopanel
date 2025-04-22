/**
 * YouTube Toggle Manager
 * 
 * Handles toggling YouTube status on track listing pages
 */
class YouTubeToggleManager {
    /**
     * Create a new YouTubeToggleManager instance
     * 
     * @param {Object} options Configuration options
     * @param {string} options.toggleButtonSelector The selector for toggle buttons (default: '.toggle-youtube-status')
     * @param {string} options.toggleEndpoint The endpoint for toggling status (default: '/youtube/toggle-enabled')
     * @param {boolean} options.showNotifications Whether to show notifications (default: true)
     */
    constructor(options = {}) {
        // Default options
        this.options = {
            toggleButtonSelector: '.toggle-youtube-status',
            toggleEndpoint: '/youtube/toggle-enabled',
            showNotifications: true,
            ...options
        };
        
        // State
        this.isToggling = false;
        
        // Initialize
        this.init();
    }

    /**
     * Initialize the toggle manager
     */
    init() {
        // Set up event listeners for toggle buttons
        document.addEventListener('click', (event) => {
            const toggleButton = event.target.closest(this.options.toggleButtonSelector);
            if (toggleButton) {
                event.preventDefault();
                this.handleToggleClick(toggleButton);
            }
        });
        
        console.log('YouTube Toggle Manager initialized');
    }

    /**
     * Handle toggle button click
     * 
     * @param {HTMLElement} button The button element
     */
    async handleToggleClick(button) {
        // Get track ID from button or closest parent with data-track-id
        const trackId = button.dataset.trackId || 
                       button.closest('[data-track-id]')?.dataset.trackId ||
                       button.form?.querySelector('[name="track_id"]')?.value;
        
        if (!trackId) {
            console.error('No track ID found for toggle button');
            return;
        }
        
        // Prevent multiple toggles at once
        if (this.isToggling) {
            console.log('Already processing a toggle action. Please wait...');
            return;
        }
        
        this.isToggling = true;
        
        // Show loading state on the button
        const originalButtonHTML = button.innerHTML;
        button.classList.add('loading');
        button.disabled = true;
        
        try {
            // Get CSRF token
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            // Make AJAX request to toggle status
            const response = await fetch(this.options.toggleEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ track_id: trackId })
            });
            
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Process response
            if (data.success) {
                // Update UI based on the new status
                this.updateButtonState(button, data.enabled);
                
                // Show success notification
                if (this.options.showNotifications) {
                    this.showNotification('success', data.message);
                }
            } else {
                throw new Error(data.message || 'Failed to toggle YouTube status');
            }
        } catch (error) {
            console.error('Error toggling YouTube status:', error);
            
            // Show error notification
            if (this.options.showNotifications) {
                this.showNotification('danger', 'Failed to toggle YouTube status: ' + error.message);
            }
            
            // Reset button state
            button.innerHTML = originalButtonHTML;
        } finally {
            // Reset toggle state
            button.classList.remove('loading');
            button.disabled = false;
            this.isToggling = false;
        }
    }

    /**
     * Update button state based on enabled status
     * 
     * @param {HTMLElement} button The button element
     * @param {boolean} enabled Whether YouTube is enabled
     */
    updateButtonState(button, enabled) {
        // Handle form button (for uploads page)
        if (button.tagName === 'BUTTON' && button.type === 'submit' && button.form) {
            if (enabled) {
                button.classList.remove('btn-outline-success');
                button.classList.add('btn-success');
                button.querySelector('i')?.classList?.remove('bi-check-circle');
                button.querySelector('i')?.classList?.add('bi-check-circle-fill');
                button.title = 'Disable YouTube';
            } else {
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-success');
                button.querySelector('i')?.classList?.remove('bi-check-circle-fill');
                button.querySelector('i')?.classList?.add('bi-check-circle');
                button.title = 'Enable YouTube';
            }
            return;
        }
        
        // For tracks index page buttons, we may need to handle different UI structures
        // This would need to be customized based on the actual UI in your application
        // For now, reload the page to ensure consistent UI if on tracks page
        const onTracksPage = window.location.pathname.includes('/tracks');
        if (onTracksPage) {
            window.location.reload();
        }
    }

    /**
     * Show a notification message
     * 
     * @param {string} type The notification type (success, danger, info, warning)
     * @param {string} message The message to display
     */
    showNotification(type, message) {
        // Check if we have the toast function available globally (from TrackStatusManager)
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }
        
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

// Make sure the YouTubeToggleManager is globally available
window.YouTubeToggleManager = YouTubeToggleManager;

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = YouTubeToggleManager;
}

// Auto-initialize when DOM content is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Check if we have toggle buttons on the page
    const toggleButtons = document.querySelectorAll('.toggle-youtube-status');
    if (toggleButtons.length > 0) {
        console.log(`Found ${toggleButtons.length} YouTube toggle buttons, initializing manager`);
        window.youtubeToggleManager = new YouTubeToggleManager();
    }
}); 