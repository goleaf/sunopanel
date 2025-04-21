@props([
    'type' => 'info',
    'message' => '',
    'dismissable' => true,
    'duration' => 5000,
    'position' => 'top-right',
    'id' => 'notification-'.uniqid()
])

@php
    $typeClasses = [
        'info' => 'bg-blue-50 text-blue-800 border-blue-500',
        'success' => 'bg-green-50 text-green-800 border-green-500',
        'warning' => 'bg-yellow-50 text-yellow-800 border-yellow-500',
        'error' => 'bg-red-50 text-red-800 border-red-500',
    ][$type] ?? 'bg-blue-50 text-blue-800 border-blue-500';

    $iconClasses = [
        'info' => 'text-blue-400',
        'success' => 'text-green-400',
        'warning' => 'text-yellow-400',
        'error' => 'text-red-400',
    ][$type] ?? 'text-blue-400';

    $positionClasses = [
        'top-right' => 'top-4 right-4',
        'top-left' => 'top-4 left-4',
        'top-center' => 'top-4 left-1/2 transform -translate-x-1/2',
        'bottom-right' => 'bottom-4 right-4',
        'bottom-left' => 'bottom-4 left-4',
        'bottom-center' => 'bottom-4 left-1/2 transform -translate-x-1/2',
    ][$position] ?? 'top-4 right-4';

    $icons = [
        'info' => '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>',
        'success' => '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
        'warning' => '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
        'error' => '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
    ][$type] ?? $icons['info'];
@endphp

<div 
    x-cloak
    x-data="{ 
        show: false,
        message: '{{ $message }}',
        init() {
            window.addEventListener('load', () => {
                if (this.message) {
                    this.show = true;
                    if ({{ $duration }}) {
                        setTimeout(() => { this.show = false }, {{ $duration }});
                    }
                }
            });
            
            this.$el.addEventListener('notify', event => {
                this.message = event.detail.message || '';
                this.show = true;
                
                if ({{ $duration }}) {
                    setTimeout(() => { this.show = false }, {{ $duration }});
                }
            });
        }
    }"
    x-show="show"
    x-transition:enter="transform ease-out duration-300 transition"
    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed {{ $positionClasses }} z-50 max-w-sm notification notification-{{ $type }}"
    id="{{ $id }}"
    {{ $attributes }}
>
    <div class="rounded-md border-l-4 p-4 shadow-md {{ $typeClasses }}">
        <div class="flex items-center">
            <div class="flex-shrink-0 {{ $iconClasses }}">
                {!! $icons !!}
            </div>
            <div class="ml-3">
                <p class="text-sm" x-text="message"></p>
            </div>
            @if($dismissable)
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button 
                        type="button" 
                        @click="show = false" 
                        class="inline-flex rounded-md p-1.5 {{ $typeClasses }} focus:outline-none focus:ring-2 focus:ring-offset-2 close-button"
                    >
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@once
<style>
    [x-cloak] { display: none !important; }
</style>
<script>
    document.addEventListener('alpine:init', () => {
        // Create a notification store to handle notifications across components
        Alpine.store('notifications', {
            notifications: [],
            
            add(message, type = 'info', duration = 5000) {
                const id = `notification-${Date.now()}`;
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { id: 'main-notification', message, type, duration }
                }));
            },
            
            remove(id) {
                this.notifications = this.notifications.filter(n => n.id !== id);
            }
        });
    });
    
    function notify(id, message, options = {}) {
        const el = document.getElementById(id);
        if (el) {
            el.dispatchEvent(new CustomEvent('notify', {
                detail: { message, ...options }
            }));
        }
    }

    // Setup global notification listener - with a small delay for SSR 
    window.addEventListener('DOMContentLoaded', function() {
        window.addEventListener('notify', function(event) {
            const id = event.detail.id || 'main-notification';
            notify(id, event.detail.message, {
                type: event.detail.type || 'info'
            });
        });
    });
</script>
@endonce 