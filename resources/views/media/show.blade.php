<x-layouts.workspace title="Image details">
    <x-ui.button :href="route($admin ? 'admin.media.index' : 'media.index')" variant="ghost">Back to Media Library</x-ui.button>
    <x-ui.card :title="$media->filename" class="break-words">
        <x-ui.image-preview :src="route($admin ? 'admin.media.file' : 'media.file', $media)" :alt="$media->alt_text ?? ''" />
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="ui-help">Original filename</dt><dd>{{ $media->original_filename ?? $media->filename }}</dd></div>
            <div><dt class="ui-help">MIME type</dt><dd>{{ $media->mime }}</dd></div>
            <div><dt class="ui-help">File format</dt><dd>{{ strtoupper($media->extension) }}</dd></div>
            <div><dt class="ui-help">File size</dt><dd>{{ number_format($media->bytes) }} bytes</dd></div>
            <div><dt class="ui-help">Dimensions</dt><dd>{{ $media->width }} × {{ $media->height }}</dd></div>
            <div><dt class="ui-help">Upload date</dt><dd>{{ $media->created_at->toDateString() }}</dd></div>
        </dl>
        @if($usedForBranding || $usedForAvatar)
            <div class="mt-3 flex flex-wrap gap-2">
                @if($usedForBranding)<x-ui.badge>Used in branding</x-ui.badge>@endif
                @if($usedForAvatar)<x-ui.badge>Assigned profile photo</x-ui.badge>@endif
            </div>
        @endif
    </x-ui.card>
    @unless($admin)
    <x-ui.card title="Image SEO metadata">
        <form method="post" action="{{ route('media.update', $media) }}" class="grid gap-3"
            x-data="{ dirty: false, initial: null }" x-init="initial = JSON.stringify([...new FormData($el)])"
            @input="dirty = JSON.stringify([...new FormData($el)]) !== initial"
            @submit="dirty = false"
            @beforeunload.window="if (dirty) { $event.preventDefault(); $event.returnValue = true }">
            @csrf
            @method('PATCH')
            <x-ui.input name="title" label="Image title" :value="old('title', $media->title)" maxlength="255" />
            <x-ui.input name="alt_text" label="ALT text" help="Describe the image content and purpose. Leave blank for a decorative image." :value="old('alt_text', $media->alt_text)" maxlength="255" />
            <x-ui.textarea name="caption" label="Caption" help="Short text to display with the image." :value="old('caption', $media->caption)" maxlength="500" rows="2" />
            <x-ui.textarea name="description" label="Description" help="Add context for future use of this image." :value="old('description', $media->description)" maxlength="4000" rows="4" />
            <div class="flex flex-wrap gap-2">
                <x-ui.button type="submit" busy-on-submit>Save changes</x-ui.button>
                <x-ui.button :href="route('media.index', absolute: false)" variant="ghost">Cancel</x-ui.button>
            </div>
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
