@props(['title' => null, 'description' => null, 'heading' => 'h2'])
<section {{ $attributes->class('ui-card') }}>
    @if ($title || $description || isset($header))
        <header class="mb-4">
            @if ($title)
                @php($heading = in_array($heading, ['h2', 'h3', 'h4'], true) ? $heading : 'h2')
                <{{ $heading }} class="ui-card-title">{{ $title }}</{{ $heading }}>
            @endif
            @if ($description)<p class="ui-help mt-1">{{ $description }}</p>@endif
            {{ $header ?? '' }}
        </header>
    @endif
    {{ $slot }}
    @isset($footer)<footer class="mt-4 border-t border-border pt-4">{{ $footer }}</footer>@endisset
</section>
