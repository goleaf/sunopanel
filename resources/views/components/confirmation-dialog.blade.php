@props([
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed with this action?',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'confirmButtonClass' => 'btn-error',
    'cancelButtonClass' => 'btn-ghost',
    'icon' => true,
    'id' => 'confirmation-'.uniqid(),
])

<div x-data="{ isOpen: false, callback: null }" x-on:open-confirmation-dialog.window="isOpen = true; callback = $event.detail.callback" @keydown.escape.window="isOpen = false" id="{{ $id }}" {{ $attributes }}>
    {{-- Trigger --}}
    <div @click="isOpen = true" style="cursor: pointer;">
        {{ $trigger ?? '' }}
    </div>

    {{-- Modal Background --}}
    <div x-show="isOpen" 
        x-transition:enter="transition ease-out duration-200" 
        x-transition:enter-start="opacity-0" 
        x-transition:enter-end="opacity-100" 
        x-transition:leave="transition ease-in duration-150" 
        x-transition:leave-start="opacity-100" 
        x-transition:leave-end="opacity-0" 
        class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-gray-500 bg-opacity-75" 
        x-cloak>
        
        {{-- Modal --}}
        <div x-show="isOpen" 
            x-transition:enter="transition ease-out duration-300" 
            x-transition:enter-start="opacity-0 transform scale-90" 
            x-transition:enter-end="opacity-100 transform scale-100" 
            x-transition:leave="transition ease-in duration-200" 
            x-transition:leave-start="opacity-100 transform scale-100" 
            x-transition:leave-end="opacity-0 transform scale-90" 
            @click.away="isOpen = false" 
            class="modal-box relative w-full max-w-md p-6 mx-auto bg-base-100 rounded-lg shadow-xl">
            
            <div class="flex items-start justify-between">
                <div class="flex items-center">
                    @if($icon)
                    <div class="flex-shrink-0 mr-3 text-error">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    @endif
                    <h3 class="text-lg font-medium leading-6 text-base-content">
                        {{ $title }}
                    </h3>
                </div>
                <button @click="isOpen = false" class="text-base-content/70 hover:text-base-content">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="mt-4">
                <p class="text-sm text-base-content/80">
                    {{ $message }}
                </p>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button @click="isOpen = false" class="btn {{ $cancelButtonClass }}">
                        {{ $cancelText }}
                    </button>
                    <button @click="typeof callback === 'function' ? callback() : null; isOpen = false" class="btn {{ $confirmButtonClass }}">
                        {{ $confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openConfirmationDialog(id, callback) {
        window.dispatchEvent(new CustomEvent('open-confirmation-dialog', {
            detail: { callback }
        }));
    }
</script>
@endpush 