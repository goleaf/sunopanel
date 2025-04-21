import './bootstrap';
import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

// Register service worker for offline capabilities
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('Service Worker registered with scope:', registration.scope);
            })
            .catch(error => {
                console.log('Service Worker registration failed:', error);
            });
    });
}

// Initialize Livewire navigation progress
document.addEventListener('livewire:navigating', () => {
    // Add server-side navigation indicator
    document.body.classList.add('navigating');
});

document.addEventListener('livewire:navigated', () => {
    // Remove navigation indicator once server navigation completes
    document.body.classList.remove('navigating');
});

// Use defer to ensure DOM is fully loaded before initializing
window.deferLoadingAlpine = function (callback) {
    window.Alpine = Alpine;
    window.Sortable = Sortable;

    if (window.Livewire) {
        // Wait for Livewire to finish server-side rendering
        document.addEventListener('livewire:init', () => {
            callback();
        });
    } else {
        callback();
    }
};

// Alpine.js directives and data
document.addEventListener('alpine:init', () => {
    // Mobile menu toggle directive
    Alpine.directive('menu-toggle', (el) => {
        el.addEventListener('click', () => {
            const mobileMenu = document.getElementById('mobile-menu');
            if (mobileMenu) {
                mobileMenu.classList.toggle('hidden');
                mobileMenu.classList.toggle('block');
            }
        });
    });
    
    // Notification component
    Alpine.data('notifications', () => ({
        notifications: [],
        add(message, type = 'info', timeout = 5000) {
            const id = Date.now();
            this.notifications.push({ id, message, type });
            
            if (timeout) {
                setTimeout(() => {
                    this.remove(id);
                }, timeout);
            }
        },
        remove(id) {
            this.notifications = this.notifications.filter(notification => notification.id !== id);
        }
    }));
    
    // Sortable tracks data
    Alpine.data('sortableTracks', () => ({
        init() {
            const el = this.$el.querySelector('.sortable-list');
            if (!el) return;
            
            new Sortable(el, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.track-draggable',
                onEnd: (evt) => {
                    const trackIds = Array.from(evt.to.children).map(
                        item => item.dataset.trackId
                    );
                    
                    // Dispatch event for Livewire to handle
                    this.$dispatch('tracks-reordered', { trackIds });
                }
            });
        }
    }));
});

// Initialize Alpine.js with deferred loading
deferLoadingAlpine(() => {
    Alpine.start();
});

// Global event handlers - Only initialize after server render
document.addEventListener('livewire:loaded', function() {
    // Genre filter functionality
    const genreFilter = document.getElementById('genre-filter');
    if (genreFilter) {
        genreFilter.addEventListener('change', function() {
            const url = new URL(window.location);
            
            if (this.value) {
                url.searchParams.set('genre', this.value);
            } else {
                url.searchParams.delete('genre');
            }
            
            window.location = url;
        });
    }
    
    // Custom event listener for notifications
    window.addEventListener('notify', (event) => {
        if (window.Alpine) {
            const notifications = Alpine.store('notifications');
            if (notifications) {
                notifications.add(
                    event.detail.message,
                    event.detail.type || 'info',
                    event.detail.timeout || 5000
                );
            }
        }
    });

    // Global audio player controls
    setupAudioPlayers();
});

// Setup audio player functionality
function setupAudioPlayers() {
    document.querySelectorAll('audio').forEach(player => {
        // Add play/pause event listeners
        player.addEventListener('play', () => {
            // Pause other players when one starts playing
            document.querySelectorAll('audio').forEach(otherPlayer => {
                if (otherPlayer !== player && !otherPlayer.paused) {
                    otherPlayer.pause();
                }
            });
        });
        
        // Add ended event listener
        player.addEventListener('ended', () => {
            player.currentTime = 0;
            // You could trigger an event here if needed
        });
    });
}
