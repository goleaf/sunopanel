@props([
    'view' => null,
    'edit' => null,
    'delete' => null,
    'confirmMessage' => 'Are you sure you want to delete this item?',
    'size' => 'xs',
    'custom' => []
])

<div class="flex flex-wrap items-center justify-end gap-2">
    @if($view)
        <x-button 
            :href="$view" 
            color="info" 
            :size="$size" 
            type="button"
            class="inline-flex items-center"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            View
        </x-button>
    @endif
    
    @if($edit)
        <x-button 
            :href="$edit" 
            color="warning" 
            :size="$size" 
            type="button"
            class="inline-flex items-center"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit
        </x-button>
    @endif
    
    @if($delete)
        <form action="{{ $delete }}" method="POST" class="inline-block" onsubmit="return confirm('{{ $confirmMessage }}');">
            @csrf
            @method('DELETE')
            <x-button 
                type="submit" 
                color="error" 
                :size="$size"
                class="inline-flex items-center"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
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