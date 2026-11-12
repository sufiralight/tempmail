@props(['title', 'description' => null, 'icon' => 'inbox'])
<div {{ $attributes->class('ui-empty') }}>
    <span class="ui-icon-tile"><x-ui.icon :name="$icon" /></span>
    <h2 class="ui-card-title mt-3">{{ $title }}</h2>
    @if ($description)<p class="ui-help mx-auto mt-1 max-w-sm">{{ $description }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>
