@props(['name', 'src' => null, 'size' => 'md', 'decorative' => false])
@php
    $initials = Illuminate\Support\Str::of($name)->trim()->explode(' ')->filter()->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
@endphp
<span {{ $attributes->class(['ui-avatar', 'ui-avatar-sm' => $size === 'sm', 'ui-avatar-lg' => $size === 'lg']) }}
    @if($decorative) aria-hidden="true" @else role="img" aria-label="{{ $name }}" @endif>
    @if ($src)<img src="{{ $src }}" alt="" loading="lazy" class="size-full object-cover">
    @else {{ $initials }} @endif
</span>
