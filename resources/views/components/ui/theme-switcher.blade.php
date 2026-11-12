<x-ui.dropdown label="Color theme" icon-only {{ $attributes }}>
    <x-slot:trigger>
        <span x-cloak class="inline-flex">
            <x-ui.icon name="sun" :size="18" x-show="$store.theme.preference === 'light'" />
            <x-ui.icon name="moon" :size="18" x-show="$store.theme.preference === 'dark'" />
            <x-ui.icon name="monitor" :size="18" x-show="$store.theme.preference === 'system'" />
        </span>
    </x-slot:trigger>

    @foreach (['light' => 'sun', 'dark' => 'moon', 'system' => 'monitor'] as $mode => $icon)
        <button type="button" role="menuitemradio" tabindex="-1" class="ui-menu-item"
            :aria-checked="$store.theme.preference === @js($mode)" @click="$store.theme.set(@js($mode))">
            <x-ui.icon :name="$icon" :size="16" />
            <span class="flex-1 text-start">{{ ucfirst($mode) }}</span>
            <span x-show="$store.theme.preference === @js($mode)" class="inline-flex">
                <x-ui.icon name="check" :size="16" />
            </span>
        </button>
    @endforeach
</x-ui.dropdown>
