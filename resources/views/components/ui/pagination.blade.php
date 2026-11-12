@props(['paginator'])
@if ($paginator->hasPages())
    <nav aria-label="Pagination" {{ $attributes->class('flex flex-wrap items-center justify-between gap-3') }}>
        <x-ui.button :href="$paginator->previousPageUrl()" :disabled="$paginator->onFirstPage()" variant="outline" icon="chevron-left">Previous</x-ui.button>
        <span class="ui-help">Page {{ $paginator->currentPage() }}</span>
        <x-ui.button :href="$paginator->nextPageUrl()" :disabled="! $paginator->hasMorePages()" variant="outline" icon="chevron-right">Next</x-ui.button>
    </nav>
@endif
