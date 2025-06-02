@php
    $mainActions = config('navigation.main_actions');
    $importUpload = config('navigation.import_upload');
    $youtube = config('navigation.youtube');
    $system = config('navigation.system');
@endphp

<div class="hidden lg:flex items-center space-x-1">
    <!-- Main Actions -->
    <div class="flex items-center space-x-1 mr-6">
        @foreach ($mainActions as $item)
            <x-navigation.nav-link :item="$item" />
        @endforeach
    </div>

    <!-- Import & Upload -->
    <div class="flex items-center space-x-1 mr-6 pl-6 border-l border-gray-200">
        @foreach ($importUpload as $item)
            <x-navigation.nav-link :item="$item" />
        @endforeach
    </div>

    <!-- YouTube Dropdown -->
    <x-navigation.dropdown :dropdown="$youtube" />

    <!-- Settings -->
    @foreach ($system as $item)
        <x-navigation.nav-link :item="$item" />
    @endforeach
</div> 