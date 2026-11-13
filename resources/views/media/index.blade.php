<x-layouts.workspace title="Media Library" :description="number_format($collectionTotal).' '.Str::plural('file', $collectionTotal).($trash ? ' in Trash' : ($admin ? ' across the workspace' : ' in your library'))">
    @unless($admin)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border pb-3">
            <nav aria-label="Media collections" class="flex flex-wrap gap-1">
                <x-ui.button :href="route('media.index', $filters)" :variant="$trash ? 'ghost' : 'secondary'" :aria-current="$trash ? null : 'page'">All Media</x-ui.button>
                <x-ui.button :href="route('media.trash', $filters)" :variant="$trash ? 'secondary' : 'ghost'" :aria-current="$trash ? 'page' : null" icon="trash">Trash</x-ui.button>
            </nav>
            @unless($trash)
                <x-ui.modal title="Upload images">
                    <x-slot:trigger><x-ui.button icon="upload">Upload images</x-ui.button></x-slot:trigger>
                    <x-ui.media-upload />
                </x-ui.modal>
            @endunless
        </div>
    @endunless

    @php
        $grid = ($filters['layout'] ?? 'grid') === 'grid';
        $collectionRoute = $admin ? 'admin.media.index' : ($trash ? 'media.trash' : 'media.index');
    @endphp
    <x-media.filters :$filters :$admin :$trash />
    <nav aria-label="Media layout" class="flex flex-wrap gap-1">
        @foreach(['grid' => 'Grid', 'list' => 'List'] as $layout => $label)
            @php($current = $grid === ($layout === 'grid'))
            <x-ui.button :href="route($collectionRoute, array_replace($filters, ['layout' => $layout, 'page' => $items->currentPage()]))"
                :variant="$current ? 'secondary' : 'ghost'" :aria-current="$current ? 'true' : null" size="sm">{{ $label }}</x-ui.button>
        @endforeach
    </nav>

    @if($items->isEmpty())
        @if($items->total())
            <x-ui.empty-state title="Page out of range">
                <x-ui.button :href="$items->url(1)" variant="outline">Go to first page</x-ui.button>
            </x-ui.empty-state>
        @elseif(filled($filters['q'] ?? null) || filled($filters['type'] ?? null))
            <x-ui.empty-state title="No matching files" description="Try adjusting your search or filters." />
        @elseif($trash)
            <x-ui.empty-state title="Trash is empty" description="Images moved to Trash will appear here." />
        @else
            <x-ui.empty-state title="No media yet" :description="$admin ? 'Uploaded images will appear here.' : 'Upload your first image to get started.'" />
        @endif
    @else
        <div @unless($admin) x-data="mediaSelection" @pageshow.window="clear()" @endunless>
            @unless($admin)
                <form id="bulk-media" method="post" action="{{ route($trash ? 'media.restore' : 'media.bulk-delete') }}" class="mb-3 flex flex-wrap items-center gap-3 border-b border-border pb-3">
                    @csrf
                    <x-ui.checkbox label="Select all on this page" x-cloak x-ref="master" @change="togglePage($event.target.checked)" />
                    <span class="ui-help" role="status" x-text="notice || selected.length + ' selected'"></span>
                    <x-ui.button variant="ghost" size="sm" x-cloak ::disabled="!selected.length" @click="clear(true)">Deselect all</x-ui.button>
                    @if($trash)
                        <x-ui.button type="submit" size="sm" variant="outline" busy-on-submit ::disabled="!canRestore">Restore selected</x-ui.button>
                    @endif
                    <x-ui.modal :title="$trash ? 'Permanently delete selected images?' : 'Move selected images to Trash?'">
                        <x-slot:trigger>
                            <x-ui.button size="sm" variant="outline" ::disabled="!selected.length">{{ $trash ? 'Permanently delete selected' : 'Move selected to Trash' }}</x-ui.button>
                        </x-slot:trigger>
                        <p class="ui-help mb-3">{{ $trash ? 'The selected images and their files will be permanently removed. This cannot be undone.' : 'You can restore these images from Trash. Images used for branding or profile photos cannot be moved.' }}</p>
                        <x-ui.button type="submit" :formaction="$trash ? route('media.purge') : null" :name="$trash ? '_method' : null" :value="$trash ? 'DELETE' : null" variant="destructive" busy-on-submit>{{ $trash ? 'Permanently delete' : 'Move to Trash' }}</x-ui.button>
                    </x-ui.modal>
                </form>
            @endunless
            <ul @class(['grid gap-3', 'sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4' => $grid])>
                @foreach($items as $image)
                    <li class="rounded-ui border border-border bg-surface p-3" @unless($admin) :class="{ 'ring-2 ring-inset ring-focus': selected.includes('{{ $image->id }}') }" @endunless>
                        <div @class(['min-w-0 gap-3', 'grid' => $grid, 'flex items-center' => !$grid])>
                            @if($trash || !$image->thumbnail)
                                <div @class(['grid shrink-0 place-items-center rounded-ui bg-muted', 'aspect-video' => $grid, 'size-12' => !$grid])>
                                    <x-ui.icon name="image" />
                                    @unless($trash)<span class="sr-only">Preview unavailable</span>@endunless
                                </div>
                            @else
                                <img src="{{ route($admin ? 'admin.media.file' : 'media.file', ['media' => $image, 'preview' => 1]) }}" alt="{{ $image->alt_text ?? '' }}"
                                    loading="lazy" @class(['shrink-0 rounded-ui bg-muted object-contain', 'aspect-video w-full' => $grid, 'size-12' => !$grid])>
                            @endif
                            <div class="min-w-0 flex-1">
                                @if($trash)
                                    <p class="break-words text-sm font-medium">{{ $image->filename }}</p>
                                @else
                                    <a href="{{ route($admin ? 'admin.media.show' : 'media.show', $image) }}" class="break-words text-sm font-medium underline-offset-4 hover:underline">{{ $image->title ?? $image->filename }}</a>
                                @endif
                                <p class="ui-help mt-1">{{ strtoupper($image->extension) }} · {{ number_format($image->bytes) }} bytes · <time datetime="{{ $image->created_at->toDateString() }}">{{ $image->created_at->toDateString() }}</time></p>
                                @if($trash)
                                    <p class="ui-help mt-1">{{ $image->purge_requested_at ? 'Permanent deletion pending' : 'Moved to Trash '.$image->deleted_at->toDateString() }}</p>
                                @endif
                            </div>
                        </div>
                        @unless($admin)
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <x-ui.checkbox name="items[]" form="bulk-media" :value="$image->id" :data-purge-pending="$image->purge_requested_at ? '1' : null"
                                    @change="sync()" :aria-label="'Select '.$image->filename" label="Select" />
                                @if($trash)
                                    @unless($image->purge_requested_at)
                                        <form method="post" action="{{ route('media.restore') }}">
                                            @csrf
                                            <input type="hidden" name="items[]" value="{{ $image->id }}">
                                            <x-ui.button type="submit" size="sm" variant="outline" busy-on-submit>Restore</x-ui.button>
                                        </form>
                                    @endunless
                                    <x-ui.modal title="Permanently delete this image?">
                                        <x-slot:trigger><x-ui.button size="sm" variant="ghost">{{ $image->purge_requested_at ? 'Retry deletion' : 'Permanently delete' }}</x-ui.button></x-slot:trigger>
                                        <p class="ui-help mb-3">This permanently removes the image and its files. It cannot be undone.</p>
                                        <form method="post" action="{{ route('media.purge') }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="items[]" value="{{ $image->id }}">
                                            <x-ui.button type="submit" variant="destructive" busy-on-submit>Permanently delete</x-ui.button>
                                        </form>
                                    </x-ui.modal>
                                @endif
                            </div>
                        @endunless
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    <footer class="grid gap-3 border-t border-border pt-3">
        <p class="ui-help">
            @if($items->isNotEmpty())
                Showing {{ number_format($items->firstItem()) }}–{{ number_format($items->lastItem()) }} of {{ number_format($items->total()) }}
            @else
                {{ number_format($items->total()) }} {{ Str::plural('result', $items->total()) }}
            @endif
            · {{ $items->perPage() }} per page
        </p>
        @unless($items->isEmpty())<x-ui.pagination :paginator="$items" numbered />@endunless
    </footer>
</x-layouts.workspace>
