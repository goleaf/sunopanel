@props([
    'id',
    'title' => null,
    'size' => 'md', // sm, md, lg, xl
    'backdrop' => true,
    'closeButton' => true,
])

@php
    $sizes = [
        'sm' => 'modal-sm',
        'md' => 'modal-md',
        'lg' => 'modal-lg',
        'xl' => 'modal-xl',
    ];
    
    $modalClasses = implode(' ', [
        'modal',
        $sizes[$size] ?? $sizes['md'],
        $backdrop ? 'modal-backdrop' : '',
    ]);
@endphp

<div id="{{ $id }}" class="{{ $modalClasses }}" x-data="{ open: false }" x-show="open" x-cloak>
    <div class="modal-content bg-base-100 shadow-xl rounded-box">
        @if($closeButton)
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="open = false">✕</button>
        @endif
        
        @if($title)
            <h3 class="text-lg font-bold">{{ $title }}</h3>
        @endif
        
        <div class="py-4">
            {{ $slot }}
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