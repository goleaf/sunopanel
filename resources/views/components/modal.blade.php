@props([
    'title' => '',
    'maxWidth' => '2xl',
    'id' => 'modal-'.uniqid(),
    'closeButton' => true,
    'static' => false
])

@php
    $maxWidthClass = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
        '5xl' => 'sm:max-w-5xl',
        '6xl' => 'sm:max-w-6xl',
        '7xl' => 'sm:max-w-7xl',
        'full' => 'sm:max-w-full',
    ][$maxWidth];
@endphp

<div
    x-data="{ 
        show: false,
        init() {
            this.$watch('show', value => {
                if (value) {
                    document.body.classList.add('overflow-y-hidden');
                } else {
                    document.body.classList.remove('overflow-y-hidden');
                }
            });
        }
    }"
    x-on:open-modal.window="if($event.detail.id === '{{ $id }}') show = true"
    x-on:close-modal.window="if($event.detail.id === '{{ $id }}') show = false"
    @keydown.escape.window="if(!{{ $static ? 'true' : 'false' }}) show = false"
    id="{{ $id }}"
    {{ $attributes }}
>
    {{-- Trigger --}}
    <div @click="show = true" style="cursor: pointer;">
        {{ $trigger ?? '' }}
    </div>

    {{-- Modal Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 flex transform items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-500/75 p-4"
        x-cloak
    >
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            @click.away="if(!{{ $static ? 'true' : 'false' }}) show = false"
            class="relative mx-auto w-full {{ $maxWidthClass }} transform rounded-lg bg-base-100 p-6 shadow-xl transition-all"
        >
            @if($closeButton)
            <button
                @click="show = false"
                class="absolute right-4 top-4 text-base-content/70 hover:text-base-content focus:outline-none"
                type="button"
            >
                <span class="sr-only">Close</span>
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            @endif

            @if($title)
            <div class="mb-5">
                <h3 class="text-lg font-medium leading-6 text-base-content">
                    {{ $title }}
                </h3>
            </div>
            @endif

            <div class="mt-2">
                {{ $slot }}
            </div>

            @isset($footer)
            <div class="mt-5 flex justify-end space-x-3 pt-3">
                {{ $footer }}
            </div>
            @endisset
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openModal(id) {
        window.dispatchEvent(new CustomEvent('open-modal', {
            detail: { id }
        }));
    }

    function closeModal(id) {
        window.dispatchEvent(new CustomEvent('close-modal', {
            detail: { id }
        }));
    }
</script>
@endpush 