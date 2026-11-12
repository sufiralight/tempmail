@props(['variant' => 'info', 'title' => null, 'announce' => false])
@php($icons = ['success' => 'check', 'error' => 'circle-alert', 'warning' => 'triangle-alert', 'info' => 'info'])
<div {{ $attributes->class('ui-alert')->merge(['role' => $announce ? ($variant === 'error' ? 'alert' : 'status') : null]) }}>
    <x-ui.icon :name="$icons[$variant] ?? $icons['info']" :size="18" />
    <div class="min-w-0">
        @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div class="ui-help">{{ $slot }}</div>
    </div>
</div>
