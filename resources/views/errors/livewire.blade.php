<div class="bg-red-50 border border-red-200 rounded-md p-4 shadow-sm">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">{{ $message ?? 'Component Error' }}</h3>
            <div class="mt-2 text-sm text-red-700">
                <p>An error occurred while loading this component. Please try refreshing the page or contact support if the problem persists.</p>
            </div>
            <div class="mt-4">
                <button type="button" onclick="window.location.reload()" class="bg-red-100 text-red-700 px-3 py-1.5 rounded-md text-sm font-medium hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Refresh Page
                </button>
            </div>
        </div>
    </div>
</div> 