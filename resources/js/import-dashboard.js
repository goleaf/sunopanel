class ImportDashboard {
    constructor() {
        this.currentSessionId = null;
        this.progressInterval = null;
        this.statsInterval = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupSourceTypeToggle();
        this.setupTabSwitching();
        this.setupUnifiedImportOptions();
        this.startStatsRefresh();
        this.initializeDefaultTab();
    }

    initializeDefaultTab() {
        // Ensure the first tab (JSON) is active by default
        const firstTab = document.querySelector('.import-tab[data-tab="json"]');
        const firstTabContent = document.getElementById('json-tab');
        
        if (firstTab && firstTabContent) {
            // Make sure the first tab is properly styled as active
            firstTab.classList.remove('border-transparent', 'text-gray-500');
            firstTab.classList.add('active', 'border-blue-500', 'text-blue-600');
            
            // Make sure the first tab content is visible
            firstTabContent.classList.remove('hidden');
        }
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

        document.getElementById('genre-import-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleGenreImport(e.target);
        });

        document.getElementById('unified-import-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleUnifiedImport(e.target);
        });

        // Quick import buttons
        document.getElementById('quick-trending-import')?.addEventListener('click', () => {
            this.handleQuickImport('trending_songs');
        });

        document.getElementById('quick-new-import')?.addEventListener('click', () => {
            this.handleQuickImport('new_songs');
        });

        // Genre preset buttons
        document.querySelectorAll('.genre-preset').forEach(button => {
            button.addEventListener('click', (e) => {
                const genre = e.target.getAttribute('data-genre');
                const genreInput = document.querySelector('#genre-import-form input[name="genre"]');
                if (genreInput) {
                    genreInput.value = genre;
                }
            });
        });

        document.getElementById('quick-popular-import')?.addEventListener('click', () => {
            this.handleQuickImport('popular_songs');
        });

        // Control buttons
        document.getElementById('stop-import')?.addEventListener('click', () => {
            this.stopImport();
        });

        document.getElementById('refresh-stats')?.addEventListener('click', () => {
            this.refreshStats();
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

    setupTabSwitching() {
        const tabs = document.querySelectorAll('.import-tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const targetTab = tab.getAttribute('data-tab');
                
                // Update tab appearance
                tabs.forEach(t => {
                    t.classList.remove('active', 'border-blue-500', 'text-blue-600');
                    t.classList.add('border-transparent', 'text-gray-500');
                });
                
                tab.classList.remove('border-transparent', 'text-gray-500');
                tab.classList.add('active', 'border-blue-500', 'text-blue-600');
                
                // Show/hide tab content
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                document.getElementById(`${targetTab}-tab`)?.classList.remove('hidden');
            });
        });
    }

    setupUnifiedImportOptions() {
        const sourceCheckboxes = document.querySelectorAll('input[name="sources[]"]');
        const jsonOptions = document.getElementById('unified-json-options');
        const discoverOptions = document.getElementById('unified-discover-options');
        const searchOptions = document.getElementById('unified-search-options');

        sourceCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const checkedSources = Array.from(sourceCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                // Show/hide relevant option sections
                if (checkedSources.includes('json')) {
                    jsonOptions?.classList.remove('hidden');
                } else {
                    jsonOptions?.classList.add('hidden');
                }

                if (checkedSources.includes('discover')) {
                    discoverOptions?.classList.remove('hidden');
                } else {
                    discoverOptions?.classList.add('hidden');
                }

                if (checkedSources.includes('search')) {
                    searchOptions?.classList.remove('hidden');
                } else {
                    searchOptions?.classList.add('hidden');
                }
            });
        });
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

    async handleGenreImport(form) {
        const formData = new FormData(form);
        
        try {
            this.showLoading('Starting Suno Genre import...');
            
            const response = await fetch('/import/suno-genre', {
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
                this.showSuccess('Suno Genre import started successfully!');
            } else {
                this.showError(result.message || 'Failed to start Suno Genre import');
            }
        } catch (error) {
            console.error('Genre import error:', error);
            this.showError('An error occurred while starting the import');
        }
    }

    async handleQuickImport(section) {
        try {
            // Disable the button to prevent multiple clicks
            const button = document.getElementById(`quick-${section.replace('_', '-')}-import`);
            if (button) {
                button.disabled = true;
                button.innerHTML = `
                    <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Importing...
                `;
            }

            this.showLoading(`Starting quick import of ${section.replace('_', ' ')}...`);
            
            // Create form data for the quick import
            const formData = new FormData();
            formData.append('section', section);
            formData.append('page_size', '50'); // Import 50 tracks
            formData.append('pages', '1'); // Just 1 page for quick import
            formData.append('start_index', '0');
            formData.append('dry_run', 'false');
            formData.append('process', 'true'); // Auto-process the tracks
            
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
                this.showSuccess(`Quick import of ${section.replace('_', ' ')} started successfully!`);
            } else {
                this.showError(result.message || `Failed to start quick import of ${section.replace('_', ' ')}`);
            }
        } catch (error) {
            console.error('Quick import error:', error);
            this.showError('An error occurred while starting the quick import');
        } finally {
            // Re-enable the button
            const button = document.getElementById(`quick-${section.replace('_', '-')}-import`);
            if (button) {
                button.disabled = false;
                const sectionName = section.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                button.innerHTML = `
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Import ${sectionName}
                `;
            }
        }
    }

    async handleUnifiedImport(form) {
        const formData = new FormData(form);
        
        // Validate that at least one source is selected
        const sources = formData.getAll('sources[]');
        if (sources.length === 0) {
            this.showError('Please select at least one import source');
            return;
        }
        
        try {
            this.showLoading('Starting unified import...');
            
            const response = await fetch('/import/suno-all', {
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
                this.showSuccess(`Unified import started successfully! Sources: ${sources.join(', ')}`);
            } else {
                this.showError(result.message || 'Failed to start unified import');
            }
        } catch (error) {
            console.error('Unified import error:', error);
            this.showError('An error occurred while starting the import');
        }
    }

    startProgressTracking() {
        if (!this.currentSessionId) return;

        this.showProgressSection();
        
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
        const content = document.getElementById('progress-content');
        if (!content) return;

        const percentage = progress.total > 0 ? Math.round((progress.imported / progress.total) * 100) : 0;
        const statusColor = this.getStatusColor(progress.status);
        
        content.innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full ${statusColor}"></div>
                        <span class="font-medium text-gray-900">${this.formatStatus(progress.status)}</span>
                    </div>
                    <span class="text-sm text-gray-500">${percentage}%</span>
                </div>
                
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: ${percentage}%"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">${this.formatNumber(progress.imported || 0)}</div>
                        <div class="text-gray-500">Imported</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">${this.formatNumber(progress.failed || 0)}</div>
                        <div class="text-gray-500">Failed</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">${this.formatNumber(progress.total || 0)}</div>
                        <div class="text-gray-500">Total</div>
                    </div>
                </div>
                
                ${progress.message ? `
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-sm text-gray-700">${progress.message}</p>
                    </div>
                ` : ''}
                
                ${progress.error ? `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                        <p class="text-sm text-red-700">${progress.error}</p>
                    </div>
                ` : ''}
            </div>
        `;
    }

    getStatusColor(status) {
        switch (status) {
            case 'running': return 'bg-blue-500';
            case 'completed': return 'bg-green-500';
            case 'failed': return 'bg-red-500';
            default: return 'bg-gray-500';
        }
    }

    formatStatus(status) {
        switch (status) {
            case 'running': return 'Import in Progress';
            case 'completed': return 'Import Completed';
            case 'failed': return 'Import Failed';
            default: return 'Import Status Unknown';
        }
    }

    stopProgressTracking() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
        this.currentSessionId = null;
        
        // Refresh stats after import completion
        setTimeout(() => {
            this.refreshStats();
        }, 2000);
    }

    stopImport() {
        if (this.currentSessionId) {
            this.stopProgressTracking();
            this.hideProgressSection();
            this.showNotification('Import stopped', 'warning');
        }
    }

    showProgressSection() {
        const section = document.getElementById('progress-section');
        section?.classList.remove('hidden');
    }

    hideProgressSection() {
        const section = document.getElementById('progress-section');
        section?.classList.add('hidden');
    }

    startStatsRefresh() {
        // Refresh stats every 30 seconds
        this.statsInterval = setInterval(() => {
            this.refreshStats();
        }, 30000);
    }

    async refreshStats() {
        try {
            const response = await fetch('/import/stats', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                this.updateStatsDisplay(result.stats);
            }
        } catch (error) {
            console.error('Stats refresh error:', error);
        }
    }

    updateStatsDisplay(stats) {
        // Update main stats cards
        document.getElementById('total-tracks').textContent = this.formatNumber(stats.total_tracks);
        document.getElementById('completed-tracks').textContent = this.formatNumber(stats.completed_tracks);
        document.getElementById('processing-tracks').textContent = this.formatNumber(stats.processing_tracks);
        document.getElementById('pending-jobs').textContent = this.formatNumber(stats.pending_jobs);
        document.getElementById('total-genres').textContent = this.formatNumber(stats.total_genres);
        document.getElementById('pending-tracks').textContent = this.formatNumber(stats.pending_tracks);
        document.getElementById('failed-tracks').textContent = this.formatNumber(stats.failed_tracks);
        
        // Update queue status
        document.getElementById('queue-count').textContent = this.formatNumber(stats.pending_jobs);
        document.getElementById('failed-count').textContent = this.formatNumber(stats.failed_jobs);
        
        // Update progress bars
        const queueProgress = document.getElementById('queue-progress');
        const failedProgress = document.getElementById('failed-progress');
        
        if (queueProgress) {
            const queuePercentage = stats.pending_jobs > 0 ? Math.min(100, (stats.pending_jobs / 100) * 100) : 0;
            queueProgress.style.width = `${queuePercentage}%`;
        }
        
        if (failedProgress) {
            const failedPercentage = stats.failed_jobs > 0 ? 100 : 0;
            failedProgress.style.width = `${failedPercentage}%`;
        }
        
        // Update queue status text
        const queueStatus = document.getElementById('queue-status');
        if (queueStatus) {
            queueStatus.textContent = stats.pending_jobs > 0 ? 'Processing' : 'Idle';
        }
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
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;
        
        // Set colors based on type
        switch (type) {
            case 'success':
                notification.className += ' bg-green-100 border border-green-200 text-green-800';
                break;
            case 'error':
                notification.className += ' bg-red-100 border border-red-200 text-red-800';
                break;
            case 'warning':
                notification.className += ' bg-yellow-100 border border-yellow-200 text-yellow-800';
                break;
            default:
                notification.className += ' bg-blue-100 border border-blue-200 text-blue-800';
        }
        
        notification.innerHTML = `
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <button class="ml-3 text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.remove()">
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
                notification.remove();
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
document.addEventListener('DOMContentLoaded', function() {
    window.importDashboard = new ImportDashboard();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.importDashboard) {
        window.importDashboard.destroy();
    }
});