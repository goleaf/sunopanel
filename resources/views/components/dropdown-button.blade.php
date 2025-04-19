@props(['action' => '#', 'method' => 'DELETE'])

<form method="POST" action="{{ $action }}">
    @csrf
    @method($method)
    
    <button type="submit" {{ $attributes->merge(['class' => 'block w-full px-4 py-2 text-sm leading-5 text-left text-base-content hover:bg-base-200 focus:outline-none focus:bg-base-200 transition']) }}>
        {{ $slot }}
    </button>
</form> 