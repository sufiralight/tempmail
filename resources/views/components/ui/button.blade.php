@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button', 'href' => null, 'disabled' => false, 'loading' => false, 'busyOnSubmit' => false, 'icon' => null])

@php
    $variants = [
        'primary' => 'ui-button-primary', 'secondary' => 'ui-button-secondary',
        'outline' => 'ui-button-outline', 'ghost' => 'ui-button-ghost', 'destructive' => 'ui-button-destructive',
    ];
    $sizes = ['sm' => 'ui-button-sm', 'md' => 'ui-button-md', 'lg' => 'ui-button-lg'];
    $classes = ['ui-button', $variants[$variant] ?? $variants['primary'], $sizes[$size] ?? $sizes['md']];
@endphp

@if ($href !== null && ! $disabled && ! $loading)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-ui.icon :name="$icon" :size="16" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $href !== null ? 'button' : $type }}" {{ $attributes->class($classes) }}
        @disabled($disabled || $loading) @if($loading) aria-busy="true" data-loading="true" @endif
        @if($busyOnSubmit && $type === 'submit' && $href === null) data-ui-submit @endif>
        @if ($loading || $busyOnSubmit)<x-ui.spinner class="ui-button-spinner" :size="16" />@endif
        @if ($icon)<x-ui.icon :name="$icon" :size="16" class="ui-button-icon" />@endif
        {{ $slot }}
    </button>
@endif
