@props([
    'view' => null,
    'edit' => null,
    'delete' => null,
    'confirmMessage' => 'Are you sure you want to delete this item?',
    'custom' => []
])

<div class="flex items-center justify-end space-x-2">
    @if($view)
        <x-button :href="$view" color="info" size="xs">
            <x-icon name="eye" class="h-4 w-4 mr-1" />
            View
        </x-button>
    @endif
    
    @if($edit)
        <x-button :href="$edit" color="warning" size="xs">
            <x-icon name="pencil" class="h-4 w-4 mr-1" />
            Edit
        </x-button>
    @endif
    
    @if($delete)
        <form action="{{ $delete }}" method="POST" class="inline-block" onsubmit="return confirm('{{ $confirmMessage }}');">
            @csrf
            @method('DELETE')
            <x-button type="submit" color="error" size="xs">
                <x-icon name="trash" class="h-4 w-4 mr-1" />
                Delete
            </x-button>
        </form>
    @endif
    
    @if(is_array($custom) && count($custom) > 0)
        @foreach($custom as $button)
            {!! $button !!}
        @endforeach
    @endif
    
    {{ $slot }}
</div> 