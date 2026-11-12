@props(['label' => null])
<div {{ $attributes->class('ui-skeleton') }} @if($label) role="status" @else aria-hidden="true" @endif>
    @if ($label)<span class="sr-only">{{ $label }}</span>@endif
</div>
