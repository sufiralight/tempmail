@props(['name' => 'Temp Mail', 'src' => null, 'href' => null])
<{{ $href ? 'a' : 'span' }} {{ $attributes->class('ui-logo')->merge(['href' => $href]) }}>
    <span class="ui-logo-mark" aria-hidden="true">
        @if ($src)<img src="{{ $src }}" alt="" class="size-full object-contain">
        @else <x-ui.icon name="mail" :size="18" /> @endif
    </span>
    <span>{{ $name }}</span>
</{{ $href ? 'a' : 'span' }}>
