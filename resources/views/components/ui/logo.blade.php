@props(['name' => 'Temp Mail', 'src' => null, 'darkSrc' => null, 'href' => null])
<{{ $href ? 'a' : 'span' }} {{ $attributes->class('ui-logo')->merge(['href' => $href]) }}>
    <span class="ui-logo-mark" aria-hidden="true">
        <span @class(['grid size-full place-items-center', 'dark:hidden' => $darkSrc])>
            @if ($src)<img src="{{ $src }}" alt="" class="size-full object-contain">
            @else <x-ui.icon name="mail" :size="18" /> @endif
        </span>
        @if($darkSrc)<img src="{{ $darkSrc }}" alt="" class="hidden size-full object-contain dark:block">@endif
    </span>
    <span>{{ $name }}</span>
</{{ $href ? 'a' : 'span' }}>
