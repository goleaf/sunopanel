class ImportDashboard {
    constructor() {
        this.currentSessionId = null;
        this.progressInterval = null;
        this.statsInterval = null;
        
        this.init();
    }

    init() {
        this.setupTabs();
        this.setupForms();
        this.setupProgressMonitor();
        this.startStatsUpdates();
    }

    setupTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetTab = button.getAttribute('data-tab');
                
                // Update button states
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });
                
                button.classList.remove('border-transparent', 'text-gray-500');
                button.classList.add('active', 'border-blue-500', 'text-blue-600');
                
                // Update content visibility
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                document.getElementById(`${targetTab}-tab`).classList.remove('hidden');
            });
        });

        // Setup conditional form fields
        this.setupConditionalFields();
    }

    setupConditionalFields() {
        // JSON source type toggle
        const sourceTypeSelect = document.querySelector('select[name="source_type"]');
        if (sourceTypeSelect) {
            sourceTypeSelect.addEventListener('change', () => {
                const fileUpload = document.getElementById('file-upload');
                const urlInput = document.getElementById('url-input');
                
                if (sourceTypeSelect.value === 'file') {
                    fileUpload.classList.remove('hidden');
                    urlInput.classList.add('hidden');
                } else {
                    fileUpload.classList.add('hidden');
                    urlInput.classList.remove('hidden');
                }
            });
        }

        // Unified import source toggles
        const unifiedCheckboxes = document.querySelectorAll('#unified-import-form input[name="sources[]"]');
        unifiedCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateUnifiedOptions();
            });
        });
    }

    updateUnifiedOptions() {
        const discoverChecked = document.getElementById('unified-discover').checked;
        const searchChecked = document.getElementById('unified-search').checked;
        const jsonChecked = document.getElementById('unified-json').checked;

        const discoverOptions = document.getElementById('unified-discover-options');
        const searchOptions = document.getElementById('unified-search-options');
        const jsonOptions = document.getElementById('unified-json-options');

        discoverOptions.classList.toggle('hidden', !discoverChecked);
        searchOptions.classList.toggle('hidden', !searchChecked);
        jsonOptions.classList.toggle('hidden', !jsonChecked);
    }

    setupForms() {
        // JSON Import Form
        const jsonForm = document.getElementById('json-import-form');
        if (jsonForm) {
            jsonForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitForm('/import/json', new FormData(jsonForm));
            });
        }

        // Discover Import Form
        const discoverForm = document.getElementById('discover-import-form');
        if (discoverForm) {
            discoverForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitForm('/import/suno-discover', new FormData(discoverForm));
            });
        }

        // Search Import Form
        const searchForm = document.getElementById('search-import-form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitForm('/import/suno-search', new FormData(searchForm));
            });
        }

        // Unified Import Form
        const unifiedForm = document.getElementById('unified-import-form');
        if (unifiedForm) {
            unifiedForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitForm('/import/suno-all', new FormData(unifiedForm));
            });
        }
    }

    async submitForm(url, formData) {
        try {
            // Disable all submit buttons
            this.setFormSubmitting(true);

            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const result = await response.json();

            if (result.success) {
                this.currentSessionId = result.session_id;
                this.showProgressMonitor();
                this.startProgressUpdates();
                this.showNotification('Import started successfully!', 'success');
            } else {
                this.showNotification(result.message || 'Import failed to start', 'error');
                this.setFormSubmitting(false);
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showNotification('An error occurred while starting the import', 'error');
            this.setFormSubmitting(false);
        }
    }

    setFormSubmitting(submitting) {
        const submitButtons = document.querySelectorAll('form button[type="submit"]');
        submitButtons.forEach(button => {
            button.disabled = submitting;
            if (submitting) {
                button.textContent = 'Starting Import...';
                button.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                // Reset button text based on form
                const form = button.closest('form');
                if (form.id === 'json-import-form') {
                    button.textContent = 'Start JSON Import';
                } else if (form.id === 'discover-import-form') {
                    button.textContent = 'Start Discover Import';
                } else if (form.id === 'search-import-form') {
                    button.textContent = 'Start Search Import';
                } else if (form.id === 'unified-import-form') {
                    button.textContent = 'Start Unified Import';
                }
                button.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });
    }

    setupProgressMonitor() {
        const hideButton = document.getElementById('hide-progress');
        if (hideButton) {
            hideButton.addEventListener('click', () => {
                this.hideProgressMonitor();
            });
        }
    }

    showProgressMonitor() {
        const monitor = document.getElementById('progress-monitor');
        if (monitor) {
            monitor.classList.remove('hidden');
            monitor.scrollIntoView({ behavior: 'smooth' });
        }
    }

    hideProgressMonitor() {
        const monitor = document.getElementById('progress-monitor');
        if (monitor) {
            monitor.classList.add('hidden');
        }
        this.stopProgressUpdates();
    }

    startProgressUpdates() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }

        this.progressInterval = setInterval(() => {
            if (this.currentSessionId) {
                this.updateProgress();
            }
        }, 2000); // Update every 2 seconds
    }

    stopProgressUpdates() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
    }

    async updateProgress() {
        if (!this.currentSessionId) return;

        try {
            const response = await fetch(`/import/progress/${this.currentSessionId}`);
            const result = await response.json();

            if (result.success) {
                const progress = result.progress;
                this.updateProgressUI(progress);

                // Stop updates if completed or failed
                if (progress.status === 'completed' || progress.status === 'failed') {
                    this.stopProgressUpdates();
                    this.setFormSubmitting(false);
                    
                    if (progress.status === 'completed') {
                        this.showNotification('Import completed successfully!', 'success');
                    } else {
                        this.showNotification('Import failed: ' + (progress.error || progress.message), 'error');
                    }
                }
            }
        } catch (error) {
            console.error('Progress update error:', error);
        }
    }

    updateProgressUI(progress) {
        // Update progress bar
        const progressBar = document.getElementById('progress-bar');
        const progressPercentage = document.getElementById('progress-percentage');
        const progressMessage = document.getElementById('progress-message');

        if (progressBar) {
            progressBar.style.width = `${progress.progress}%`;
        }

        if (progressPercentage) {
            progressPercentage.textContent = `${progress.progress}%`;
        }

        if (progressMessage) {
            progressMessage.textContent = progress.message || 'Processing...';
        }

        // Update counters
        const importedElement = document.getElementById('progress-imported');
        const failedElement = document.getElementById('progress-failed');
        const totalElement = document.getElementById('progress-total');

        if (importedElement) {
            importedElement.textContent = progress.imported || 0;
        }

        if (failedElement) {
            failedElement.textContent = progress.failed || 0;
        }

        if (totalElement) {
            totalElement.textContent = progress.total || 0;
        }

        // Update progress bar color based on status
        if (progressBar) {
            progressBar.className = 'h-2 rounded-full transition-all duration-300';
            
            switch (progress.status) {
                case 'completed':
                    progressBar.classList.add('bg-green-600');
                    break;
                case 'failed':
                    progressBar.classList.add('bg-red-600');
                    break;
                case 'running':
                case 'processing':
                    progressBar.classList.add('bg-blue-600');
                    break;
                default:
                    progressBar.classList.add('bg-yellow-600');
            }
        }
    }

    startStatsUpdates() {
        // Update stats every 10 seconds
        this.statsInterval = setInterval(() => {
            this.updateStats();
        }, 10000);
    }

    async updateStats() {
        try {
            const response = await fetch('/import/stats');
            const result = await response.json();

            if (result.success) {
                const stats = result.stats;
                
                // Update stat elements
                const statElements = {
                    'total-tracks': stats.total_tracks,
                    'total-genres': stats.total_genres,
                    'pending-tracks': stats.pending_tracks,
                    'processing-tracks': stats.processing_tracks,
                    'completed-tracks': stats.completed_tracks,
                    'failed-tracks': stats.failed_tracks,
                    'pending-jobs': stats.pending_jobs,
                    'failed-jobs': stats.failed_jobs
                };

                Object.entries(statElements).forEach(([id, value]) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.textContent = this.formatNumber(value);
                    }
                });
            }
        } catch (error) {
            console.error('Stats update error:', error);
        }
    }

    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;
        
        // Set colors based on type
        switch (type) {
            case 'success':
                notification.classList.add('bg-green-500', 'text-white');
                break;
            case 'error':
                notification.classList.add('bg-red-500', 'text-white');
                break;
            case 'warning':
                notification.classList.add('bg-yellow-500', 'text-white');
                break;
            default:
                notification.classList.add('bg-blue-500', 'text-white');
        }

        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 5000);
    }

    destroy() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }
        if (this.statsInterval) {
            clearInterval(this.statsInterval);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.importDashboard = new ImportDashboard();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.importDashboard) {
        window.importDashboard.destroy();
    }
}); 