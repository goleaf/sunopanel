@props([
    'for',
    'value' => null, 
    'required' => false,
    'tooltip' => '',
    'tooltipPosition' => 'top',
    'tooltipColor' => 'info',
    'tooltipIcon' => 'question',
    'tooltipInteractive' => false,
])

<div class="flex items-center">
    <label for="{{ $for }}" {{ $attributes->merge(['class' => 'block text-sm font-medium text-base-content']) }}>
        {{ $value ?? $slot }}
        @if($required) <span class="text-error">*</span> @endif
    </label>
    
    @if($tooltip)
        <x-help-icon 
            :text="$tooltip" 
            :position="$tooltipPosition" 
            :color="$tooltipColor" 
            :icon="$tooltipIcon" 
            :interactive="$tooltipInteractive"
            class="ml-1.5"
        />
    @endif
</div> 