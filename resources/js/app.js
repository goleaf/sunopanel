import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Create custom Alpine.js directive for mobile menu
document.addEventListener('alpine:init', () => {
    Alpine.directive('menu-toggle', (el) => {
        el.addEventListener('click', () => {
            const mobileMenu = document.getElementById('mobile-menu');
            if (mobileMenu) {
                if (mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.remove('hidden');
                    mobileMenu.classList.add('block');
                } else {
                    mobileMenu.classList.add('hidden');
                    mobileMenu.classList.remove('block');
                }
            }
        });
    });
});

Alpine.start();

document.addEventListener('DOMContentLoaded', function() {
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
    
    // Enhanced mobile menu handling
    const hamburgerBtn = document.querySelector('button[aria-controls="mobile-menu"]') || 
                         document.querySelector('nav button');
    
    if (hamburgerBtn) {
        const mobileMenu = document.getElementById('mobile-menu') || 
                           document.querySelector('div[x-ref="mobile-menu"]') || 
                           document.querySelector('nav div.hidden.sm\\:hidden');
        
        // Add a direct click handler as a fallback
        hamburgerBtn.addEventListener('click', function(e) {
            // Only handle click if Alpine.js didn't handle it
            if (!window.Alpine || !Alpine.version) {
                e.preventDefault();
                if (mobileMenu) {
                    // Toggle mobile menu visibility manually
                    if (mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.remove('hidden');
                        mobileMenu.classList.add('block');
                    } else {
                        mobileMenu.classList.add('hidden');
                        mobileMenu.classList.remove('block');
                    }
                }
            }
        });
    }
});
