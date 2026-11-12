@props(['text', 'id'])
<span x-data="uiTooltip" {{ $attributes->class('ui-tooltip') }}
    @mouseenter="show()" @focusin="show()" @resize.window="if ($el.matches(':hover, :focus-within')) show()"
    @keydown.escape.stop="dismissed = true">
    {{ $slot }}
    <span x-ref="tooltip" role="tooltip" id="{{ $id }}" class="ui-tooltip-text" :class="{ 'ui-tooltip-dismissed': dismissed }">{{ $text }}</span>
</span>
