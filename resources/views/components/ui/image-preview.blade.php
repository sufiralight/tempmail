@props(['src', 'alt', 'thumbnail' => false])
<figure {{ $attributes->class($thumbnail ? 'ui-image-thumbnail' : 'ui-image-preview') }}>
    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" decoding="async">
    @if ($slot->isNotEmpty())<figcaption class="break-words p-2 text-sm text-foreground">{{ $slot }}</figcaption>@endif
</figure>
