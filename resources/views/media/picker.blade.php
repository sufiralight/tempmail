<x-layouts.app mode="auth" title="Choose image" heading="Choose image">
    <div x-data="{ choice: null }" @media-collection-updated="choice = null" class="grid gap-4">
        @if(session('status'))<x-ui.alert variant="success" announce>{{ session('status') }}</x-ui.alert>@endif
        @if($errors->any())<x-ui.alert variant="error" announce>{{ $errors->first() }}</x-ui.alert>@endif
        <x-ui.media-upload picker :avatar="!empty($filters['avatar'])" />
        <x-media.filters :$filters picker />
        <div data-media-collection class="grid gap-4">
        @if($items->isEmpty())<x-ui.empty-state title="No matching images" />@endif
        <fieldset class="grid grid-cols-2 gap-2">
            <legend class="ui-label">Available images</legend>
            @foreach($items as $image)
                <label data-media-id="{{ $image->id }}" class="grid min-w-0 gap-1 rounded-ui border border-border p-2 text-sm">
                    <img class="aspect-square w-full object-contain" loading="lazy" src="{{ route('media.file', ['media' => $image, 'preview' => 1], absolute: false) }}" alt="">
                    <span class="break-all"><input type="radio" name="picker-choice" value="{{ $image->id }}" class="ui-choice-input"
                        data-title="{{ $image->title }}" data-alt-text="{{ $image->alt_text }}" data-caption="{{ $image->caption }}" data-description="{{ $image->description }}"
                        data-preview="{{ route('media.file', ['media' => $image, 'preview' => 1], absolute: false) }}" @change="choice = $event.target"> {{ $image->title ?? $image->filename }}</span>
                </label>
            @endforeach
        </fieldset>
        <x-ui.button ::disabled="!choice" @click="window.parent.postMessage({ type: 'tempmail-media', id: choice.value, preview: choice.dataset.preview, metadata: { title: choice.dataset.title, alt_text: choice.dataset.altText, caption: choice.dataset.caption, description: choice.dataset.description } }, location.origin)">Confirm selection</x-ui.button>
        <x-ui.pagination :paginator="$items" />
        </div>
    </div>
</x-layouts.app>
