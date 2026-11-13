@props(['title', 'description' => null])
<x-layouts.workspace :$title :$description>
    <div class="max-w-3xl space-y-4">
        <nav aria-label="Account settings" class="flex flex-wrap gap-1 border-b border-border pb-2">
            @foreach(['profile.show' => 'Profile', 'profile.security' => 'Security', 'profile.preferences' => 'Preferences'] as $route => $label)
                @php
                    $current = request()->routeIs($route);
                    $section = $route === 'profile.security' && request()->routeIs('profile.sessions');
                @endphp
                <x-ui.button :href="route($route, absolute: false)" size="sm" :variant="$current || $section ? 'secondary' : 'ghost'"
                    :aria-current="$current ? 'page' : null">{{ $label }}</x-ui.button>
            @endforeach
        </nav>
        {{ $slot }}
    </div>
</x-layouts.workspace>
