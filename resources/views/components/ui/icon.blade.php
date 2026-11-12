@props(['name', 'size' => 20, 'label' => null])

<svg {{ $attributes->class('shrink-0')->merge([
    'width' => $size, 'height' => $size, 'viewBox' => '0 0 24 24',
    'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '2',
    'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'focusable' => 'false',
] + ($label ? ['role' => 'img', 'aria-label' => $label] : ['aria-hidden' => 'true'])) }}>
    <use href="{{ asset('icons/lucide.svg') }}#{{ $name === 'alert-circle' ? 'circle-alert' : $name }}"></use>
</svg>
