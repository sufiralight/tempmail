@props(['size' => 20, 'label' => null])

@if ($label)
    <span {{ $attributes->class('inline-flex items-center') }} role="status">
        <x-ui.icon name="loader-circle" :size="$size" class="motion-safe:animate-spin" />
        <span class="sr-only">{{ $label }}</span>
    </span>
@else
    <x-ui.icon name="loader-circle" :size="$size" {{ $attributes->class('motion-safe:animate-spin') }} />
@endif
