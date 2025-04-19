<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Component Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-8">
        <h1 class="text-2xl font-bold mb-6">Components Demo</h1>
        
        <section class="mb-10">
            <h2 class="text-xl font-semibold mb-4">Button Component</h2>
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium mb-3">Button Variants</h3>
                    <div class="flex flex-wrap gap-2">
                        <x-button variant="primary">Primary</x-button>
                        <x-button variant="secondary">Secondary</x-button>
                        <x-button variant="success">Success</x-button>
                        <x-button variant="danger">Danger</x-button>
                        <x-button variant="warning">Warning</x-button>
                        <x-button variant="info">Info</x-button>
                        <x-button variant="light">Light</x-button>
                        <x-button variant="dark">Dark</x-button>
                        <x-button variant="outline">Outline</x-button>
                        <x-button variant="link">Link</x-button>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium mb-3">Button Sizes</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-button size="xs">Extra Small</x-button>
                        <x-button size="sm">Small</x-button>
                        <x-button size="md">Medium</x-button>
                        <x-button size="lg">Large</x-button>
                        <x-button size="xl">Extra Large</x-button>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium mb-3">Button with Icons</h3>
                    <div class="flex flex-wrap gap-2">
                        <x-button icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>'>
                            Add New
                        </x-button>
                        
                        <x-button 
                            variant="success" 
                            icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
                            iconPosition="right"
                        >
                            Confirm
                        </x-button>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium mb-3">Disabled Button</h3>
                    <x-button disabled>Disabled Button</x-button>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium mb-3">Full Width Button</h3>
                    <x-button fullWidth>Full Width Button</x-button>
                </div>
            </div>
        </section>
        
        <section class="mb-10">
            <h2 class="text-xl font-semibold mb-4">Notification Component</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-medium mb-3">Trigger Notifications</h3>
                    <div class="flex flex-wrap gap-2">
                        <x-button 
                            variant="info" 
                            onclick="notify('info', 'This is an info notification')"
                        >
                            Show Info
                        </x-button>
                        
                        <x-button 
                            variant="success" 
                            onclick="notify('success', 'Operation completed successfully!')"
                        >
                            Show Success
                        </x-button>
                        
                        <x-button 
                            variant="warning" 
                            onclick="notify('warning', 'Warning: This action cannot be undone')"
                        >
                            Show Warning
                        </x-button>
                        
                        <x-button 
                            variant="danger" 
                            onclick="notify('error', 'An error occurred while processing your request')"
                        >
                            Show Error
                        </x-button>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium mb-3">Custom Duration</h3>
                    <x-button 
                        onclick="notify('info', 'This notification will stay for 10 seconds', 10000)"
                    >
                        Long Duration (10s)
                    </x-button>
                </div>
                
                <div>
                    <h3 class="text-lg font-medium mb-3">Custom Position</h3>
                    <div class="flex flex-wrap gap-2">
                        <x-button 
                            variant="secondary" 
                            onclick="notify('info', 'Top right notification', 5000, 'top-right')"
                        >
                            Top Right
                        </x-button>
                        
                        <x-button 
                            variant="secondary" 
                            onclick="notify('info', 'Top left notification', 5000, 'top-left')"
                        >
                            Top Left
                        </x-button>
                        
                        <x-button 
                            variant="secondary" 
                            onclick="notify('info', 'Bottom right notification', 5000, 'bottom-right')"
                        >
                            Bottom Right
                        </x-button>
                        
                        <x-button 
                            variant="secondary" 
                            onclick="notify('info', 'Bottom left notification', 5000, 'bottom-left')"
                        >
                            Bottom Left
                        </x-button>
                    </div>
                </div>
            </div>
        </section>
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