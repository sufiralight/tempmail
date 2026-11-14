@props(['name', 'label', 'value' => null, 'previewUrl' => null, 'avatar' => false, 'replace' => false])
<div x-data="mediaPicker" class="grid gap-2">
    <p class="ui-label">{{ $label }}</p>
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" x-ref="selection">
    <img x-init="preview = $el.dataset.initial || ''" data-initial="{{ $previewUrl }}"
        hidden :hidden="!preview" :src="preview || null" alt="Selected image preview" class="size-20 rounded-ui object-contain">
    <div class="flex flex-wrap gap-2">
        <x-ui.modal :title="'Choose image for '.$label">
            <x-slot:trigger><x-ui.button variant="outline" @click="opened = true">{{ $replace && $value ? 'Replace image' : 'Choose image' }}</x-ui.button></x-slot:trigger>
            <template x-if="opened">
                <iframe title="Media Library image picker" src="{{ route('media.picker', $avatar ? ['avatar' => 1] : [], absolute: false) }}"
                    class="h-[55dvh] w-full rounded-ui border border-border"></iframe>
            </template>
        </x-ui.modal>
        <x-ui.button variant="ghost" @click="$refs.selection.value = ''; preview = ''; status = 'Selection removed.'; $dispatch('media-cleared')">Remove selection</x-ui.button>
    </div>
    <p role="status" class="ui-help" x-text="status"></p>
</div>
