@props(['variant' => 'muted', 'icon' => null])
<span {{ $attributes->class(['ui-badge', 'ui-badge-outline' => $variant === 'outline', 'ui-badge-solid' => $variant === 'solid']) }}>
    @if ($icon)<x-ui.icon :name="$icon" :size="14" />@endif
    {{ $slot }}
</span>
