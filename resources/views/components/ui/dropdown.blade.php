@props(['label', 'icon' => 'chevron-down', 'align' => 'end', 'iconOnly' => false])

<div x-data="uiMenu" x-id="['menu-trigger', 'menu-panel']" {{ $attributes->class('ui-dropdown') }}
    @focusout="if (!$el.contains($event.relatedTarget)) close()" @resize.window="position()">
    <button type="button" x-ref="trigger" :id="$id('menu-trigger')" :aria-controls="$id('menu-panel')"
        aria-haspopup="menu" aria-expanded="false" :aria-expanded="opened" aria-label="{{ $label }}"
        class="ui-button ui-button-outline ui-button-md" @click="opened ? close(true) : show()"
        @keydown.down.prevent="show()" @keydown.up.prevent="show('last')" @keydown.escape.stop="close(true)">
        @isset($trigger){{ $trigger }}@else
            @unless($iconOnly){{ $label }}@endunless
            <x-ui.icon :name="$icon" :size="18" />
        @endisset
    </button>
    <div x-ref="menu" hidden :hidden="!opened" :id="$id('menu-panel')" :aria-labelledby="$id('menu-trigger')"
        role="menu" tabindex="-1" class="ui-menu {{ $align === 'start' ? 'start-0' : 'end-0' }}"
        @click.outside="if (!$refs.trigger.contains($event.target)) close($el.contains(document.activeElement))"
        @keydown="navigate($event)" @keydown.escape.stop.prevent="close(true)"
        @click="const item = $event.target.closest('[role^=menuitem]'); if (item && item.getAttribute('aria-disabled') !== 'true') close(true)">
        {{ $slot }}
    </div>
</div>
