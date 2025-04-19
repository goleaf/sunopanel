<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Components Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Dashboard Components Demo</h1>
        
        <!-- Dashboard Widgets Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-dashboard-widget 
                title="Total Tracks" 
                value="1,248" 
                color="primary"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" /></svg>'
                trend="up"
                trendValue="12% this week"
            />
            
            <x-dashboard-widget 
                title="Total Playlists" 
                value="386" 
                color="accent"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>'
                trend="up"
                trendValue="5% this week"
            />
            
            <x-dashboard-widget 
                title="Active Users" 
                value="824" 
                color="success"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>'
                trend="up"
                trendValue="8% this month"
            />
            
            <x-dashboard-widget 
                title="Storage Used" 
                value="3.2 GB" 
                color="warning"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>'
                trend="up"
                trendValue="15% this month"
            />
        </div>
        
        <!-- Stats Cards Row -->
        <h2 class="text-xl font-semibold mb-4">Stats Cards</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <x-stats-card
                title="New Uploads"
                value="124"
                comparison="vs last month"
                trend="up"
                percentage="18"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>'
                detailsLink="#"
            />
            
            <x-stats-card
                title="Total Downloads"
                value="3,827"
                comparison="vs last month"
                trend="up"
                percentage="12"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>'
                iconBg="bg-success/10"
                iconColor="text-success"
                detailsLink="#"
            />
            
            <x-stats-card
                title="Playlist Engagement"
                value="67%"
                comparison="vs last month"
                trend="down"
                percentage="3"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
                iconBg="bg-error/10"
                iconColor="text-error"
                detailsLink="#"
            />
        </div>
        
        <!-- Stats Charts -->
        <h2 class="text-xl font-semibold mb-4">Analytics Charts</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <x-stats-chart
                id="monthly-tracks"
                title="Monthly Track Uploads"
                description="Number of tracks uploaded per month"
                type="bar"
                :labels="json_encode(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'])"
                :datasets="json_encode([
                    [
                        'label' => 'Tracks Uploaded',
                        'data' => [65, 78, 52, 91, 83, 108, 94, 120, 136, 145, 112, 130],
                        'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'borderWidth' => 1
                    ]
                ])"
                height="300px"
            />
            
            <x-stats-chart
                id="user-growth"
                title="User Growth"
                description="New registrations over time"
                type="line"
                :labels="json_encode(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'])"
                :datasets="json_encode([
                    [
                        'label' => 'New Users',
                        'data' => [25, 35, 45, 40, 55, 65, 75, 85, 95, 110, 125, 145],
                        'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                        'borderColor' => 'rgb(16, 185, 129)',
                        'borderWidth' => 2,
                        'tension' => 0.3,
                        'fill' => true
                    ]
                ])"
                height="300px"
            />
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <x-stats-chart
                id="genre-distribution"
                title="Genre Distribution"
                description="Tracks by genre category"
                type="pie"
                :labels="json_encode(['Electronic', 'Hip Hop', 'Rock', 'Pop', 'Jazz', 'Classical', 'Other'])"
                :datasets="json_encode([
                    [
                        'label' => 'Tracks per Genre',
                        'data' => [35, 25, 15, 12, 8, 5, 10],
                        'backgroundColor' => [
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(239, 68, 68, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(20, 184, 166, 0.7)',
                            'rgba(156, 163, 175, 0.7)'
                        ],
                        'borderColor' => '#fff',
                        'borderWidth' => 1
                    ]
                ])"
                height="300px"
            />
            
            <x-stats-chart
                id="engagement-metrics"
                title="Engagement Metrics"
                description="Comparison of key engagement metrics"
                type="line"
                :labels="json_encode(['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'])"
                :datasets="json_encode([
                    [
                        'label' => 'Plays',
                        'data' => [150, 180, 230, 300, 280, 320],
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'tension' => 0.4,
                        'fill' => false
                    ],
                    [
                        'label' => 'Downloads',
                        'data' => [45, 60, 75, 90, 85, 100],
                        'borderColor' => 'rgb(16, 185, 129)',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'tension' => 0.4,
                        'fill' => false
                    ],
                    [
                        'label' => 'Playlist Adds',
                        'data' => [30, 40, 55, 65, 70, 85],
                        'borderColor' => 'rgb(245, 158, 11)',
                        'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                        'tension' => 0.4,
                        'fill' => false
                    ]
                ])"
                height="300px"
            />
        </div>
        
        <!-- Activity Card -->
        <h2 class="text-xl font-semibold mb-4">Recent Activity</h2>
        <div class="mb-8">
            <x-activity-card
                title="Recent Activity"
                :activities="[
                    [
                        'avatar' => 'https://ui-avatars.com/api/?name=John+Doe&background=3b82f6&color=fff',
                        'description' => '<strong>John Doe</strong> uploaded a new track <strong>"Summer Vibes"</strong>',
                        'time' => '5 minutes ago',
                        'action' => ['url' => '#', 'label' => 'View']
                    ],
                    [
                        'avatar' => 'https://ui-avatars.com/api/?name=Jane+Smith&background=10b981&color=fff',
                        'description' => '<strong>Jane Smith</strong> created a new playlist <strong>"Workout Mix 2023"</strong>',
                        'time' => '25 minutes ago',
                        'action' => ['url' => '#', 'label' => 'View']
                    ],
                    [
                        'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-5 w-5 text-warning\" viewBox=\"0 0 20 20\" fill=\"currentColor\"><path fill-rule=\"evenodd\" d=\"M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z\" clip-rule=\"evenodd\" /></svg>',
                        'description' => 'System maintenance scheduled for <strong>tomorrow at 2:00 AM UTC</strong>',
                        'time' => '1 hour ago',
                        'details' => 'Expected downtime: 30 minutes'
                    ],
                    [
                        'avatar' => 'https://ui-avatars.com/api/?name=Alex+Johnson&background=6366f1&color=fff',
                        'description' => '<strong>Alex Johnson</strong> commented on <strong>"Midnight Dreams"</strong>',
                        'time' => '3 hours ago',
                        'action' => ['url' => '#', 'label' => 'View']
                    ],
                    [
                        'icon' => '<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-5 w-5 text-success\" viewBox=\"0 0 20 20\" fill=\"currentColor\"><path fill-rule=\"evenodd\" d=\"M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z\" clip-rule=\"evenodd\" /></svg>',
                        'description' => 'Storage quota increased to <strong>5GB</strong>',
                        'time' => '5 hours ago'
                    ]
                ]"
                viewAllLink="#"
            />
        </div>
        
        <!-- Notification Cards -->
        <h2 class="text-xl font-semibold mb-4">System Notifications</h2>
        <div class="space-y-4 mb-8">
            <x-notification-card
                type="info"
                title="Information"
                message="The next scheduled maintenance will be on July 15, 2023. Plan accordingly."
            />
            
            <x-notification-card
                type="success"
                title="Success"
                message="Your storage quota has been successfully increased to 5GB."
            />
            
            <x-notification-card
                type="warning"
                title="Warning"
                message="Your storage usage is at 85% of your quota. Consider upgrading your plan or removing unused files."
            />
            
            <x-notification-card
                type="error"
                title="Error"
                message="There was an error processing your last upload. Please try again or contact support if the issue persists."
            />
        </div>
        
        <!-- Interactive Components -->
        <h2 class="text-xl font-semibold mb-4">Interactive Components</h2>
        <div class="flex flex-wrap gap-4 mb-8">
            <x-modal id="sample-modal" title="Sample Modal">
                <x-slot name="trigger">
                    <x-button>Open Modal</x-button>
                </x-slot>
                
                <p class="mb-4">This is a sample modal dialog using the modal component.</p>
                <p>Modals are perfect for focused interactions, confirmations, and smaller forms.</p>
                
                <x-slot name="footer">
                    <x-button variant="secondary" @click="show = false">Cancel</x-button>
                    <x-button variant="primary">Confirm</x-button>
                </x-slot>
            </x-modal>
            
            <x-confirmation-dialog
                title="Confirm Deletion"
                message="Are you sure you want to delete this item? This action cannot be undone."
                confirmText="Delete"
                cancelText="Cancel"
            >
                <x-slot name="trigger">
                    <x-button variant="danger">Delete Item</x-button>
                </x-slot>
            </x-confirmation-dialog>
            
            <x-button 
                variant="primary" 
                onclick="window.dispatchEvent(new CustomEvent('notify', {
                    detail: { 
                        type: 'info', 
                        message: 'Notification triggered from button click!',
                        duration: 5000,
                        position: 'top-right'
                    }
                }))"
            >
                Show Notification
            </x-button>
        </div>
    </div>
    
    <!-- Include the notification component -->
    <x-notification />
    
    <script>
        function notify(type, message, duration = 5000, position = 'top-right') {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { type, message, duration, position }
            }));
        }
    </script>
</body>
</html> 