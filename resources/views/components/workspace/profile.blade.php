@props(['title', 'description' => null])
<x-layouts.workspace :$title :$description>
    <nav aria-label="Account settings" class="flex flex-wrap gap-2 border-b border-border pb-3">
        @foreach (['show' => 'Personal information', 'security' => 'Security', 'sessions' => 'Active sessions', 'preferences' => 'Preferences'] as $page => $label)
            @php($current = request()->routeIs('profile.'.$page))
            <x-ui.button :href="route('profile.'.$page, absolute: false)" :variant="$current ? 'secondary' : 'ghost'" :aria-current="$current ? 'page' : null">{{ $label }}</x-ui.button>
        @endforeach
    </nav>
    {{ $slot }}
</x-layouts.workspace>
