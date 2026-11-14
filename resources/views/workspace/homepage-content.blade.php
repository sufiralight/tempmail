<x-layouts.workspace :title="$language->native_name.' · Homepage'">
    <x-workspace.locale-navigation :$language />
    <x-ui.button :href="route('admin.languages.preview', $language->id)" variant="outline">Preview saved draft</x-ui.button>
    <p class="ui-help">Empty published fields use canonical English. An inactive language stays hidden even after publishing.</p>
    <form method="post" action="{{ route('admin.languages.homepage.save', $language->id) }}" class="grid gap-4">
        @csrf
        @method('PATCH')
        <input type="hidden" name="revision" value="{{ old('revision', $revision) }}">
        @foreach($entries as $entry)
            <section class="grid gap-3 border-b border-border pb-4 md:grid-cols-2">
                <div lang="en" dir="ltr">
                    <p class="ui-label">{{ $entry->identifier }} · English source</p>
                    <p class="break-words">{{ config('ui_translations')[$entry->identifier] }}</p>
                    <p class="ui-help">{{ $entry->status === 'published' ? 'Published value exists' : 'English fallback' }}</p>
                </div>
                <x-ui.textarea :name="'values['.$entry->id.']'" :error="$errors->first('values.'.$entry->id)"
                    :label="$language->native_name.' · '.$entry->identifier" :dir="$language->direction" :lang="$language->code"
                    :value="old('values.'.$entry->id, $entry->draft_value ?? $entry->value)" maxlength="4000" rows="3" />
            </section>
        @endforeach
        <div class="flex flex-wrap gap-2">
            <x-ui.button type="submit" name="action" value="draft" variant="outline" busy-on-submit loading-label="Saving draft…">Save draft</x-ui.button>
            <x-ui.button type="submit" name="action" value="publish" busy-on-submit loading-label="Publishing…">Publish homepage</x-ui.button>
        </div>
    </form>
</x-layouts.workspace>
