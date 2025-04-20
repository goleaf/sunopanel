<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Component Test</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Notification Component Test</h1>
        
        <div class="bg-white shadow-md rounded p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Flash Messages</h2>
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif
            
            @if(session('warning'))
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                    {{ session('warning') }}
                </div>
            @endif
            
            @if(session('info'))
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                    {{ session('info') }}
                </div>
            @endif
            
            <div class="mt-4">
                <a href="{{ route('test.notification', ['success' => 'Success notification message']) }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mr-2">
                    Show Success
                </a>
                <a href="{{ route('test.notification', ['error' => 'Error notification message']) }}" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded mr-2">
                    Show Error
                </a>
                <a href="{{ route('test.notification', ['warning' => 'Warning notification message']) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-2">
                    Show Warning
                </a>
                <a href="{{ route('test.notification', ['info' => 'Info notification message']) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Show Info
                </a>
            </div>
        </div>
        
        <div class="bg-white shadow-md rounded p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">JavaScript Notifications</h2>
            <div class="mt-4">
                <button onclick="showNotification('success', 'Success notification message')" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mr-2">
                    JS Success
                </button>
                <button onclick="showNotification('error', 'Error notification message')" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded mr-2">
                    JS Error
                </button>
                <button onclick="showNotification('warning', 'Warning notification message')" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-2">
                    JS Warning
                </button>
                <button onclick="showNotification('info', 'Info notification message')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    JS Info
                </button>
            </div>
        </div>
        
        <div class="bg-white shadow-md rounded p-6">
            <h2 class="text-xl font-semibold mb-4">Custom Notifications</h2>
            <div class="mt-4">
                <button onclick="showCustomNotification('Custom notification with longer text that demonstrates how the notification system handles multi-line content.')" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                    Custom Notification
                </button>
            </div>
        </div>
    </div>
    
    <script>
        function showNotification(type, message) {
            // Simple notification implementation
            const notificationDiv = document.createElement('div');
            notificationDiv.className = `fixed top-4 right-4 p-4 rounded shadow-lg ${getTypeClass(type)}`;
            notificationDiv.textContent = message;
            document.body.appendChild(notificationDiv);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                notificationDiv.remove();
            }, 3000);
        }
        
        function showCustomNotification(message) {
            // Custom notification with more styling
            const notificationDiv = document.createElement('div');
            notificationDiv.className = 'fixed bottom-4 left-4 p-4 bg-purple-100 border-l-4 border-purple-500 text-purple-700 max-w-md rounded shadow-lg';
            notificationDiv.textContent = message;
            document.body.appendChild(notificationDiv);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notificationDiv.remove();
            }, 5000);
        }
        
        function getTypeClass(type) {
            switch(type) {
                case 'success': return 'bg-green-100 text-green-700 border border-green-400';
                case 'error': return 'bg-red-100 text-red-700 border border-red-400';
                case 'warning': return 'bg-yellow-100 text-yellow-700 border border-yellow-400';
                case 'info': return 'bg-blue-100 text-blue-700 border border-blue-400';
                default: return 'bg-gray-100 text-gray-700 border border-gray-400';
            }
        }
    </script>
</body>
</html> 