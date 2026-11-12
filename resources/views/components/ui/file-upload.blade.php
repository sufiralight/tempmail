@props(['progress' => null, 'progressLabel' => 'Upload progress'])
<div class="space-y-2">
    <x-ui.control type="file" {{ $attributes->except(['as', 'type', 'value']) }} />
    @if ($progress !== null)
        <progress max="100" value="{{ min(100, max(0, (float) $progress)) }}" aria-label="{{ $progressLabel }}" class="w-full"></progress>
    @endif
    {{ $slot }}
</div>
