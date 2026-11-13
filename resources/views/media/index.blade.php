<x-layouts.workspace title="Media Library" description="Upload and organize images in your private library.">
    <x-ui.card title="Upload"><x-ui.media-upload /></x-ui.card>
    <x-ui.card><x-media.filters :$filters /></x-ui.card>
    @if($items->isEmpty())
        <x-ui.empty-state title="No images found" description="Upload an image or change your filters." />
    @else
        <form id="bulk-media" action="{{ route('media.bulk-delete') }}" method="post" x-data="{ selected: [] }">
            @csrf
            <div class="mb-4 flex flex-wrap gap-3">
                <x-ui.checkbox label="Select all on this page" @change="selected = $event.target.checked ? [...$el.closest('form').querySelectorAll('[name=&quot;items[]&quot;]')].map(input => input.value) : []" />
                <x-ui.modal title="Delete selected images?">
                    <x-slot:trigger><x-ui.button variant="outline" ::disabled="!selected.length">Delete selected</x-ui.button></x-slot:trigger>
                    <p class="ui-help mb-3">Images used in global branding cannot be deleted.</p>
                    <x-ui.button type="submit" variant="destructive">Confirm deletion</x-ui.button>
                </x-ui.modal>
            </div>
            <ul @class(['grid gap-3', 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4' => ($filters['layout'] ?? 'grid') === 'grid'])>
                @foreach($items as $image)
                    <li class="ui-card min-w-0">
                        <a href="{{ route('media.show', $image) }}" class="block break-all">
                            <img src="{{ route('media.file', ['media' => $image, 'preview' => 1]) }}" alt="{{ $image->alt_text ?? '' }}"
                                loading="lazy" @class(['mb-2 rounded-ui bg-muted object-contain', 'aspect-square w-full' => ($filters['layout'] ?? 'grid') === 'grid', 'size-16' => ($filters['layout'] ?? '') === 'list'])>
                            <span class="text-sm font-medium">{{ $image->title ?? $image->filename }}</span>
                        </a>
                        <x-ui.checkbox name="items[]" :value="$image->id" x-model="selected" :aria-label="'Select '.$image->filename" label="Select" />
                    </li>
                @endforeach
            </ul>
        </form>
        <x-ui.pagination :paginator="$items" />
    @endif
</x-layouts.workspace>
