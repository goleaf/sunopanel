@props([
    'action' => '#', 
    'method' => 'DELETE',
    'color' => 'base',
    'size' => 'sm',
    'confirmMessage' => null
])

<form method="POST" action="{{ $action }}" class="inline-block">
    @csrf
    @method($method)
    
    <button 
        type="submit" 
        {{ $confirmMessage ? "onclick=\"return confirm('{$confirmMessage}')\"" : '' }}
        {{ $attributes->merge(['class' => "btn btn-{$color} btn-{$size} w-full text-left justify-start"]) }}
    >
        {{ $slot }}
    </button>
</form> 