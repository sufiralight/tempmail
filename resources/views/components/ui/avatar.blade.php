@props(['name', 'src' => null, 'size' => 'md', 'decorative' => false])
@php
    $words = preg_split('/\s+/u', $name, 3, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = implode('', array_map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)), array_slice($words, 0, 2))) ?: '?';
@endphp
<span {{ $attributes->class(['ui-avatar relative', 'ui-avatar-sm' => $size === 'sm', 'ui-avatar-lg' => $size === 'lg']) }}
    @if($decorative) aria-hidden="true" @else role="img" aria-label="{{ filled($name) ? 'Avatar for '.$name : 'Account avatar' }}" @endif>
    <span aria-hidden="true">{{ $initials }}</span>
    @if($src)
        <img src="{{ $src }}" alt="" loading="lazy" hidden x-data="{ loaded: false }"
            :hidden="!loaded" x-init="loaded = $el.complete && $el.naturalWidth > 0"
            x-on:load="loaded = true" x-on:error="loaded = false" class="absolute inset-0 size-full object-cover">
    @endif
</span>
