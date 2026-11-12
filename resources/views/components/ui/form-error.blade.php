@props(['message' => null])
@if ($message || $slot->isNotEmpty())
    <p {{ $attributes->class('ui-error') }}>{{ $message ?? $slot }}</p>
@endif
