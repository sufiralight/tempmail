@props(['caption'])
<div {{ $attributes->class('ui-table-scroll') }} role="region" aria-label="{{ $caption }}" tabindex="0">
    <table class="ui-table">
        <caption class="sr-only">{{ $caption }}</caption>
        <thead>{{ $head }}</thead>
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
