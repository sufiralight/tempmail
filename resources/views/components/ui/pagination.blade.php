@props(['paginator', 'numbered' => false])
@if ($paginator->hasPages())
    <nav aria-label="Pagination" {{ $attributes->class('flex flex-wrap items-center justify-between gap-3') }}>
        <x-ui.button :href="$paginator->previousPageUrl()" :disabled="$paginator->onFirstPage()" variant="outline" icon="chevron-left">Previous</x-ui.button>
        @if($numbered && $paginator->currentPage() <= $paginator->lastPage())
            <div class="flex flex-wrap items-center gap-1">
                @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                    @if($page === $paginator->currentPage())
                        <span class="ui-button ui-button-secondary ui-button-sm" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
                    @else
                        <x-ui.button :href="$url" size="sm" variant="ghost" :aria-label="'Page '.$page">{{ $page }}</x-ui.button>
                    @endif
                @endforeach
            </div>
        @else
            <span class="ui-help">Page {{ $paginator->currentPage() }}</span>
        @endif
        <x-ui.button :href="$paginator->nextPageUrl()" :disabled="! $paginator->hasMorePages()" variant="outline" icon="chevron-right">Next</x-ui.button>
    </nav>
@endif
