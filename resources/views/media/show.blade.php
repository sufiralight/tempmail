<x-layouts.workspace title="Image details">
    <x-ui.button :href="route('media.index')" variant="ghost">Back to Media Library</x-ui.button>
    <x-ui.card :title="$media->filename" class="break-words">
        <x-ui.image-preview :src="route('media.file', $media)" :alt="$media->alt_text ?? ''" />
        <p class="ui-help mt-3">{{ $media->mime }} · {{ $media->width }} × {{ $media->height }} · {{ number_format($media->bytes) }} bytes · {{ $media->created_at->toDateString() }}</p>
    </x-ui.card>
    <x-ui.card title="Metadata">
        <form method="post" action="{{ route('media.update', $media) }}" class="grid gap-3">
            @csrf
            @method('PATCH')
            <x-ui.input name="title" label="Title" :value="old('title', $media->title)" maxlength="255" />
            <x-ui.input name="alt_text" label="Alternative text" :value="old('alt_text', $media->alt_text)" maxlength="255" />
            <x-ui.button type="submit">Save details</x-ui.button>
        </form>
        <x-ui.modal title="Delete this image?" class="mt-4">
            <x-slot:trigger><x-ui.button variant="outline">Delete image</x-ui.button></x-slot:trigger>
            <form method="post" action="{{ route('media.destroy', $media) }}">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="destructive">Confirm deletion</x-ui.button>
            </form>
        </x-ui.modal>
    </x-ui.card>
</x-layouts.workspace>
