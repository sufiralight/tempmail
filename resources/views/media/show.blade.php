<x-layouts.workspace title="Image details">
    <x-ui.button :href="route($admin ? 'admin.media.index' : 'media.index')" variant="ghost">Back to Media Library</x-ui.button>
    <x-ui.card :title="$media->filename" class="break-words">
        <x-ui.image-preview :src="route($admin ? 'admin.media.file' : 'media.file', $media)" :alt="$media->alt_text ?? ''" />
        <p class="ui-help mt-3">{{ $media->mime }} · {{ $media->width }} × {{ $media->height }} · {{ number_format($media->bytes) }} bytes · {{ $media->created_at->toDateString() }}</p>
    </x-ui.card>
    @unless($admin)
    <x-ui.card title="Metadata">
        <form method="post" action="{{ route('media.update', $media) }}" class="grid gap-3">
            @csrf
            @method('PATCH')
            <x-ui.input name="title" label="Title" :value="old('title', $media->title)" maxlength="255" />
            <x-ui.input name="alt_text" label="Alternative text" :value="old('alt_text', $media->alt_text)" maxlength="255" />
            <x-ui.button type="submit">Save details</x-ui.button>
        </form>
        <x-ui.modal title="Move this image to Trash?" class="mt-4">
            <x-slot:trigger><x-ui.button variant="outline">Move to Trash</x-ui.button></x-slot:trigger>
            <form method="post" action="{{ route('media.destroy', $media) }}">
                @csrf
                @method('DELETE')
                <p class="ui-help mb-3">You can restore this image from Trash.</p>
                <x-ui.button type="submit" variant="destructive" busy-on-submit>Move to Trash</x-ui.button>
            </form>
        </x-ui.modal>
    </x-ui.card>
    @endunless
</x-layouts.workspace>
