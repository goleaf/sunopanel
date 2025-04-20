@props([
    'placeholder' => 'Search...',
    'wire' => null,
])

<div class="relative flex items-center">
    <input
        type="text"
        @if($wire) wire:model.debounce.500ms="{{ $wire }}" @endif
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500']) }}
    >
    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
</div> 