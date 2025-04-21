<div x-data="{ darkMode: $persist(false) }" 
     @class(['fixed top-4 right-4 z-50' => $attributes->get('fixed')])
>
    <button 
        @click="darkMode = !darkMode; document.documentElement.setAttribute('data-theme', darkMode ? 'dark' : 'light')"
        class="btn btn-circle btn-ghost"
    >
        <span x-show="!darkMode" class="text-xl">🌞</span>
        <span x-show="darkMode" class="text-xl">🌙</span>
    </button>
</div> 