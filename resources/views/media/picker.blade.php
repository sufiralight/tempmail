<x-layouts.app mode="auth" title="Choose image" heading="Choose image">
    <div x-data="{ choice: null }" class="grid gap-4">
        @if(session('status'))<x-ui.alert variant="success" announce>{{ session('status') }}</x-ui.alert>@endif
        @if($errors->any())<x-ui.alert variant="error" announce>{{ $errors->first() }}</x-ui.alert>@endif
        <x-ui.media-upload picker />
        <x-media.filters :$filters picker />
        @if($items->isEmpty())<x-ui.empty-state title="No matching images" />@endif
        <fieldset class="grid grid-cols-2 gap-2">
            <legend class="ui-label">Available images</legend>
            @foreach($items as $image)
                <label class="grid min-w-0 gap-1 rounded-ui border border-border p-2 text-sm">
                    <img class="aspect-square w-full object-contain" loading="lazy" src="{{ route('media.file', ['media' => $image, 'preview' => 1], absolute: false) }}" alt="">
                    <span class="break-all"><input type="radio" name="picker-choice" value="{{ $image->id }}" class="ui-choice-input"
                        data-preview="{{ route('media.file', ['media' => $image, 'preview' => 1], absolute: false) }}" @change="choice = $event.target"> {{ $image->title ?? $image->filename }}</span>
                </label>
            @endforeach
        </fieldset>
        <x-ui.button ::disabled="!choice" @click="window.parent.postMessage({ type: 'tempmail-media', id: choice.value, preview: choice.dataset.preview }, location.origin)">Confirm selection</x-ui.button>
        <x-ui.pagination :paginator="$items" />
    </div>
</x-layouts.app>
