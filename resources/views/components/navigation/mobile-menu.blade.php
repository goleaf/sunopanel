@php
    $mainActions = config('navigation.main_actions');
    $importUpload = config('navigation.import_upload');
    $youtube = config('navigation.youtube');
    $system = config('navigation.system');
@endphp

<div id="mobile-menu" class="lg:hidden hidden border-t border-gray-200 bg-white">
    <div class="px-4 py-3 space-y-1">
        <!-- Main Actions -->
        <div class="space-y-1 mb-4">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 py-2">Main</div>
            @foreach ($mainActions as $item)
                <x-navigation.nav-link :item="$item" :mobile="true" />
            @endforeach
        </div>

        <!-- Import & Upload -->
        <div class="space-y-1 mb-4">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 py-2">Import & Upload</div>
            @foreach ($importUpload as $item)
                <x-navigation.nav-link :item="$item" :mobile="true" />
            @endforeach
        </div>

        <!-- YouTube -->
        <div class="space-y-1 mb-4">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 py-2">YouTube</div>
            @foreach ($youtube['items'] as $item)
                <x-navigation.nav-link :item="$item" :mobile="true" />
            @endforeach
        </div>

        <!-- Settings -->
        <div class="space-y-1">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 py-2">System</div>
            @foreach ($system as $item)
                <x-navigation.nav-link :item="$item" :mobile="true" />
            @endforeach
        </div>
    </div>
</div> 