<div class="p-4 bg-red-50 border border-red-200 rounded-lg">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">
                An error occurred while rendering this component
            </h3>
            @if(app()->environment('local', 'development'))
                <div class="mt-2 text-xs text-red-700 bg-red-100 p-2 rounded overflow-auto max-h-48">
                    {{ $message ?? 'Unknown error' }}
                </div>
            @else
                <p class="text-sm text-red-700 mt-1">
                    Please try refreshing the page.
                </p>
            @endif
        </div>
    </div>
</div> 