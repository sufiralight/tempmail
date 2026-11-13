@props(['picker' => false, 'avatar' => false])
<form method="post" action="{{ route('media.store') }}" enctype="multipart/form-data" data-page-progress="off"
    x-data="mediaUpload" @submit.prevent="upload($el)" @dragover.prevent="dragging = true" @dragleave="dragging = false"
    @drop.prevent="drop($event, $refs.files)" :class="{ 'ring-2 ring-focus': dragging }" class="grid gap-3 rounded-ui border border-border p-4">
    @csrf
    @if($avatar)<input type="hidden" name="avatar" value="1">@endif
    @if($picker)<input type="hidden" name="picker" value="1">@endif
    <x-ui.input type="file" name="image" label="Choose or drop images" accept="image/jpeg,image/png,image/webp,image/gif" x-ref="files" ::disabled="busy" multiple required />
    <noscript><p class="ui-help">Select one image at a time when JavaScript is disabled.</p></noscript>
    <div class="flex flex-wrap gap-2">
        <x-ui.button type="submit" ::disabled="busy">Upload images</x-ui.button>
        <x-ui.button variant="outline" x-show="busy" x-cloak @click="cancel()">Cancel uploads</x-ui.button>
    </div>
    <p class="ui-help" role="status" x-text="notice"></p>
    <template x-for="(entry, index) in entries" :key="index">
        <div class="ui-help break-all">
            <span x-text="entry.name"></span>
            <progress max="100" :value="entry.progress" x-show="entry.state === 'Uploading'" :aria-label="'Uploading ' + entry.name"></progress>
            <span x-text="entry.state"></span>
            @unless($picker)
                <a x-show="entry.id" :href="entry.id ? '/media/' + entry.id : null" class="ms-1 underline underline-offset-4">View uploaded item</a>
            @else
                <a x-show="entry.id && !entry.visible" :href="entry.id ? '/media/picker?sort=newest{{ $avatar ? '&avatar=1' : '' }}' : null"
                    class="ms-1 underline underline-offset-4">Browse newest images</a>
            @endunless
        </div>
    </template>
</form>
