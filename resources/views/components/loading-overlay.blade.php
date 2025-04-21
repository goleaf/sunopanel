<div x-data="{ isLoading: false }" 
     x-init="
        window.addEventListener('loading-start', () => isLoading = true);
        window.addEventListener('loading-end', () => isLoading = false);
     "
     x-show="isLoading"
     class="fixed inset-0 bg-base-100 bg-opacity-50 z-50 flex items-center justify-center"
>
    <div class="loading loading-lg text-primary"></div>
</div> 