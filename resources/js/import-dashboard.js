class ImportDashboard {
    constructor() {
        this.currentSessionId = null;
        this.progressInterval = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupSourceTypeToggle();
    }

    setupEventListeners() {
        // Form submissions
        document.getElementById('json-import-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleJsonImport(e.target);
        });

        document.getElementById('discover-import-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleDiscoverImport(e.target);
        });

        document.getElementById('search-import-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSearchImport(e.target);
        });

        // Modal close
        document.getElementById('close-modal')?.addEventListener('click', () => {
            this.hideProgressModal();
        });

        // Click outside modal to close
        document.getElementById('progress-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'progress-modal') {
                this.hideProgressModal();
            }
        });
    }

    setupSourceTypeToggle() {
        const sourceTypeSelect = document.querySelector('select[name="source_type"]');
        const fileInput = document.getElementById('file-input');
        const urlInput = document.getElementById('url-input');

        if (sourceTypeSelect) {
            sourceTypeSelect.addEventListener('change', (e) => {
                if (e.target.value === 'file') {
                    fileInput?.classList.remove('hidden');
                    urlInput?.classList.add('hidden');
                } else {
                    fileInput?.classList.add('hidden');
                    urlInput?.classList.remove('hidden');
                }
            });
        }
    }

    async handleJsonImport(form) {
        const formData = new FormData(form);
        
        try {
            this.showLoading('Starting JSON import...');
            
            const response = await fetch('/import/json', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                this.currentSessionId = result.session_id;
                this.startProgressTracking();
                this.showSuccess('JSON import started successfully!');
            } else {
                this.showError(result.message || 'Failed to start JSON import');
            }
        } catch (error) {
            console.error('JSON import error:', error);
            this.showError('An error occurred while starting the import');
        }
    }

    async handleDiscoverImport(form) {
        const formData = new FormData(form);
        
        try {
            this.showLoading('Starting Suno Discover import...');
            
            const response = await fetch('/import/suno-discover', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                this.currentSessionId = result.session_id;
                this.startProgressTracking();
                this.showSuccess('Suno Discover import started successfully!');
            } else {
                this.showError(result.message || 'Failed to start Suno Discover import');
            }
        } catch (error) {
            console.error('Discover import error:', error);
            this.showError('An error occurred while starting the import');
        }
    }

    async handleSearchImport(form) {
        const formData = new FormData(form);
        
        try {
            this.showLoading('Starting Suno Search import...');
            
            const response = await fetch('/import/suno-search', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                this.currentSessionId = result.session_id;
                this.startProgressTracking();
                this.showSuccess('Suno Search import started successfully!');
            } else {
                this.showError(result.message || 'Failed to start Suno Search import');
            }
        } catch (error) {
            console.error('Search import error:', error);
            this.showError('An error occurred while starting the import');
        }
    }

    startProgressTracking() {
        if (!this.currentSessionId) return;

        this.showProgressModal();
        
        // Clear any existing interval
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }

        // Start tracking progress
        this.progressInterval = setInterval(() => {
            this.updateProgress();
        }, 2000); // Update every 2 seconds

        // Initial progress update
        this.updateProgress();
    }

    async updateProgress() {
        if (!this.currentSessionId) return;

        try {
            const response = await fetch(`/import/progress/${this.currentSessionId}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const progress = await response.json();
            
            if (progress) {
                this.displayProgress(progress);
                
                // Stop tracking if completed or failed
                if (progress.status === 'completed' || progress.status === 'failed') {
                    this.stopProgressTracking();
                }
            }
        } catch (error) {
            console.error('Progress update error:', error);
        }
    }

    displayProgress(progress) {
        const content = document.getElementById('modal-progress-content');
        if (!content) return;

        const percentage = progress.total > 0 ? Math.round((progress.imported / progress.total) * 100) : 0;
        
        let statusColor = 'blue';
        if (progress.status === 'completed') statusColor = 'green';
        if (progress.status === 'failed') statusColor = 'red';

        content.innerHTML = `
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>${progress.message || 'Processing...'}</span>
                        <span>${percentage}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-${statusColor}-600 h-2 rounded-full transition-all duration-300" style="width: ${percentage}%"></div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-green-600">${progress.imported || 0}</div>
                        <div class="text-sm text-gray-600">Imported</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-red-600">${progress.failed || 0}</div>
                        <div class="text-sm text-gray-600">Failed</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-blue-600">${progress.total || 0}</div>
                        <div class="text-sm text-gray-600">Total</div>
                    </div>
                </div>

                <div class="text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${statusColor}-100 text-${statusColor}-800">
                        ${progress.status.charAt(0).toUpperCase() + progress.status.slice(1)}
                    </span>
                </div>

                ${progress.status === 'completed' || progress.status === 'failed' ? `
                    <div class="text-center">
                        <button onclick="importDashboard.hideProgressModal()" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition duration-200">
                            Close
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    stopProgressTracking() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
    }

    showProgressModal() {
        const modal = document.getElementById('progress-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    hideProgressModal() {
        const modal = document.getElementById('progress-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
        this.stopProgressTracking();
        this.currentSessionId = null;
        
        // Refresh page statistics
        this.refreshStats();
    }

    async refreshStats() {
        try {
            const response = await fetch('/import/stats', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const stats = await response.json();
            
            if (stats) {
                // Update statistics on the page
                this.updateStatsDisplay(stats);
            }
        } catch (error) {
            console.error('Stats refresh error:', error);
        }
    }

    updateStatsDisplay(stats) {
        // Update the statistics cards with new data
        const elements = {
            'total-tracks': stats.total_tracks,
            'completed-tracks': stats.completed_tracks,
            'processing-tracks': stats.processing_tracks,
            'failed-tracks': stats.failed_tracks,
            'pending-jobs': stats.pending_jobs,
            'failed-jobs': stats.failed_jobs
        };

        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = this.formatNumber(value);
            }
        });
    }

    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    showLoading(message) {
        this.showNotification(message, 'info');
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;
        
        let bgColor = 'bg-blue-500';
        let textColor = 'text-white';
        
        if (type === 'success') {
            bgColor = 'bg-green-500';
        } else if (type === 'error') {
            bgColor = 'bg-red-500';
        }
        
        notification.className += ` ${bgColor} ${textColor}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.importDashboard = new ImportDashboard();
}); 