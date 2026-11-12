@props(['variant' => 'info', 'title' => null])
<div x-data="{ shown: true }" x-show="shown" {{ $attributes->class('ui-toast') }}
    @keydown.escape.stop="shown = false">
    <x-ui.alert :$variant :$title announce class="flex-1 border-0 p-0">{{ $slot }}</x-ui.alert>
    <x-ui.button size="sm" variant="ghost" icon="x" aria-label="Dismiss notification" @click="shown = false" />
</div>
