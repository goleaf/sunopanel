@props([
    'view' => null,
    'edit' => null,
    'delete' => null,
    'confirmMessage' => 'Are you sure you want to delete this item?',
    'custom' => []
])

<div class="flex items-center justify-end space-x-2">
    @if($view)
        <a href="{{ $view }}" class="text-indigo-600 hover:text-indigo-900" title="View">
            <x-icon name="eye" class="h-5 w-5" />
        </a>
    @endif
    
    @if($edit)
        <a href="{{ $edit }}" class="text-blue-600 hover:text-blue-900" title="Edit">
            <x-icon name="pencil" class="h-5 w-5" />
        </a>
    @endif
    
    @if($delete)
        <form action="{{ $delete }}" method="POST" class="inline-block" onsubmit="return confirm('{{ $confirmMessage }}');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                <x-icon name="trash" class="h-5 w-5" />
            </button>
        </form>
    @endif
    
    @if(is_array($custom) && count($custom) > 0)
        @foreach($custom as $button)
            {!! $button !!}
        @endforeach
    @endif
    
    {{ $slot }}
</div> 