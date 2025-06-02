<!-- Modern Navigation -->
<nav class="bg-white/95 backdrop-blur-sm border-b border-gray-200/50 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo and Brand -->
            <x-navigation.brand />

            <!-- Desktop Navigation -->
            <x-navigation.desktop-menu />

            <!-- Mobile Menu Button -->
            <x-navigation.mobile-toggle />
        </div>
    </div>

    <!-- Mobile Navigation -->
    <x-navigation.mobile-menu />
</nav> 