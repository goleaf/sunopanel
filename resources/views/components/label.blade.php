@props(['for', 'value' => null, 'required' => false])

<label for="{{ $for }}" {{ $attributes->merge(['class' => 'block text-sm font-medium text-base-content mb-1']) }}>
    {{ $value ?? $slot }}
    @if($required) <span class="text-error">*</span> @endif
</label> 