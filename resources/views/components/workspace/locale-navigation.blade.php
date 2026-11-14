@props(['language'])
<nav aria-label="Language settings" class="flex flex-wrap gap-2 border-b border-border pb-3">
    <x-ui.button :href="route('admin.languages.index')" size="sm" variant="ghost" icon="arrow-left">Languages</x-ui.button>
    @foreach(['admin.languages.show' => 'Overview', 'admin.languages.homepage' => 'Homepage'] as $route => $label)
        @php
            $current = request()->routeIs($route);
        @endphp
        <x-ui.button :href="route($route, $language->id)" size="sm" :variant="$current ? 'secondary' : 'ghost'"
            :aria-current="$current ? 'page' : null">{{ $label }}</x-ui.button>
    @endforeach
    <x-ui.button :href="route('admin.fonts.index', ['language' => $language->id])" size="sm" variant="ghost">Open in Typography Studio</x-ui.button>
</nav>
