@props(['picker' => false])
<form method="post" action="{{ route('media.store') }}" enctype="multipart/form-data" data-page-progress="off"
    x-data="mediaUpload" @submit.prevent="upload($el)" @dragover.prevent="dragging = true" @dragleave="dragging = false"
    @drop.prevent="drop($event, $refs.files)" :class="{ 'ring-2 ring-focus': dragging }" class="grid gap-3 rounded-ui border border-border p-4">
    @csrf
    @if($picker)<input type="hidden" name="picker" value="1">@endif
    <x-ui.input type="file" name="image" label="Choose or drop images" accept="image/jpeg,image/png,image/webp,image/gif" x-ref="files" required />
    <span x-init="$refs.files.multiple = true"></span>
    <div class="flex flex-wrap gap-2">
        <x-ui.button type="submit" ::disabled="busy">Upload images</x-ui.button>
        <x-ui.button variant="outline" x-show="busy" x-cloak @click="cancel()">Cancel uploads</x-ui.button>
        <x-ui.button variant="ghost" x-show="finished" x-cloak @click="location.reload()">Refresh library</x-ui.button>
    </div>
    <template x-for="(entry, index) in entries" :key="index">
        <div class="ui-help break-all">
            <span x-text="entry.name"></span>
            <progress max="100" :value="entry.progress" x-show="entry.state === 'Uploading'" :aria-label="'Uploading ' + entry.name"></progress>
            <span role="status" x-text="entry.state"></span>
        </div>
    </template>
</form>
