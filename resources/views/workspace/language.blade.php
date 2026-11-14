<x-layouts.workspace :title="$language->native_name">
    <x-workspace.locale-navigation :$language />
    <x-ui.card>
        <dl class="mb-4 grid grid-cols-2 gap-2 text-sm">
            <dt>Locale code</dt><dd><code>{{ $language->code }}</code></dd>
            <dt>Default language</dt><dd>{{ $defaultId === (int) $language->id ? 'Yes' : 'No' }}</dd>
        </dl>
        <form method="post" action="{{ route('admin.languages.update', $language->id) }}" class="grid gap-4">
            @csrf
            @method('PATCH')
            <x-ui.input name="name" label="Language name" :value="old('name', $language->name)" required maxlength="100" />
            <x-ui.input name="native_name" label="Native name" :value="old('native_name', $language->native_name)" :dir="$language->direction" required maxlength="100" />
            <x-ui.select name="direction" label="Direction">
                <option value="ltr" @selected(old('direction', $language->direction) === 'ltr')>Left to right</option>
                <option value="rtl" @selected(old('direction', $language->direction) === 'rtl')>Right to left</option>
            </x-ui.select>
            <x-ui.select name="is_active" label="Visibility">
                <option value="1" @selected((bool) old('is_active', $language->is_active))>Active</option>
                <option value="0" @selected(! old('is_active', $language->is_active)) @disabled($defaultId === (int) $language->id)>Inactive</option>
            </x-ui.select>
            <x-ui.input name="sort_order" label="Sort order" type="number" :value="old('sort_order', $language->sort_order)" min="0" max="1000000" required />
            <x-ui.button type="submit" busy-on-submit loading-label="Saving…">Save changes</x-ui.button>
        </form>
        @if($defaultId !== (int) $language->id && $language->is_active)
            <form method="post" action="{{ route('admin.languages.default', $language->id) }}" class="mt-4 border-t border-border pt-4">
                @csrf
                <p class="ui-help mb-2">The default language is used when no active language has been selected.</p>
                <x-ui.button type="submit" variant="outline" busy-on-submit loading-label="Updating…">Set as default</x-ui.button>
            </form>
        @endif
    </x-ui.card>
</x-layouts.workspace>
