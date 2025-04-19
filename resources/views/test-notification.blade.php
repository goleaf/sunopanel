<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Notification Test Page</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-base-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Notification Component Test</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="card bg-base-200 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Flash Messages</h2>
                    <p>Click the buttons below to test flash messages:</p>
                    <div class="flex flex-wrap gap-2 mt-4">
                        <a href="{{ route('test.flash', ['type' => 'success']) }}" class="btn btn-success">Success</a>
                        <a href="{{ route('test.flash', ['type' => 'error']) }}" class="btn btn-error">Error</a>
                        <a href="{{ route('test.flash', ['type' => 'warning']) }}" class="btn btn-warning">Warning</a>
                        <a href="{{ route('test.flash', ['type' => 'info']) }}" class="btn btn-info">Info</a>
                    </div>
                </div>
            </div>
            
            <div class="card bg-base-200 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">JavaScript Notifications</h2>
                    <p>Click the buttons below to trigger JavaScript notifications:</p>
                    <div class="flex flex-wrap gap-2 mt-4">
                        <button class="btn btn-success" onclick="showNotification('success', 'Success notification message')">Success</button>
                        <button class="btn btn-error" onclick="showNotification('error', 'Error notification message')">Error</button>
                        <button class="btn btn-warning" onclick="showNotification('warning', 'Warning notification message')">Warning</button>
                        <button class="btn btn-info" onclick="showNotification('info', 'Info notification message')">Info</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card bg-base-200 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Custom Notifications</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-control w-full">
                            <div class="label">
                                <span class="label-text">Message</span>
                            </div>
                            <input type="text" id="custom-message" placeholder="Enter your message" class="input input-bordered w-full" />
                        </label>
                    </div>
                    <div>
                        <label class="form-control w-full">
                            <div class="label">
                                <span class="label-text">Type</span>
                            </div>
                            <select id="custom-type" class="select select-bordered w-full">
                                <option value="info">Info</option>
                                <option value="success">Success</option>
                                <option value="warning">Warning</option>
                                <option value="error">Error</option>
                            </select>
                        </label>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button class="btn btn-primary" onclick="showCustomNotification()">Show Notification</button>
                </div>
            </div>
        </div>
        
        <div class="fixed z-50 pointer-events-none w-full flex justify-center top-4 left-0">
            <x-notification id="main-notification" position="top-center" />
        </div>
    </div>
    
    <script>
        function showNotification(type, message) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    message: message,
                    type: type
                }
            }));
        }
        
        function showCustomNotification() {
            const message = document.getElementById('custom-message').value || 'Custom notification message';
            const type = document.getElementById('custom-type').value;
            
            showNotification(type, message);
        }
    </script>
</body>
</html> 