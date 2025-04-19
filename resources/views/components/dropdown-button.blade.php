@props(['action' => '#'])

<form method="POST" action="{{ $action }}">
    @csrf
    @method('DELETE')
    
    <button type="submit" {{ $attributes->merge(['class' => 'block w-full px-4 py-2 text-sm leading-5 text-left text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition']) }}>
        {{ $slot }}
    </button>
</form> 