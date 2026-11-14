<x-layouts.workspace title="Translation catalog" description="Manage interface translations across your supported languages.">
    <x-ui.card>
        <form method="get" action="{{ route('admin.ui-translations.index', absolute: false) }}" class="grid items-end gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_2fr_auto]">
            <x-ui.select name="language" label="Language">
                @foreach($languages as $locale)
                    <option value="{{ $locale->id }}" @selected($locale->id === $language->id)>{{ $locale->name }} · {{ $locale->native_name }}{{ $locale->is_active ? '' : ' (inactive)' }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="group" label="Group">
                <option value="">All groups</option>
                @foreach($groups as $group)
                    <option value="{{ $group }}" @selected(($filters['group'] ?? '') === $group)>{{ ucfirst($group) }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="status" label="Status">
                <option value="">All statuses</option>
                @foreach(['missing' => 'Missing', ...$statuses] as $status => $label)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input name="q" type="search" label="Search key or source" :value="$filters['q'] ?? null" maxlength="100" />
            <x-ui.button type="submit" variant="outline" icon="search">Filter</x-ui.button>
        </form>
    </x-ui.card>
    @if($entry)
        <x-ui.card>
            <h2 class="ui-section-title">Edit {{ $language->name }} translation</h2>
            <p class="ui-help mt-2"><code dir="ltr">{{ $entry->identifier }}</code></p>
            <p class="mt-2" lang="en" dir="ltr">{{ $entry->source }}</p>
            @if($entry->context)<p class="ui-help mt-2">{{ $entry->context }}</p>@endif
            @if($fonts)<x-layouts.typography :$fonts :$stylesheet preview />@endif
            <form method="post" action="{{ route('admin.ui-translations.update', [$language->id, $entry->id], absolute: false) }}" class="mt-4 grid gap-3"
                x-data="{ dirty: false }" x-on:input="dirty = true" x-on:change="dirty = true"
                x-on:beforeunload.window="if (dirty) { $event.preventDefault(); $event.returnValue = '' }"
                x-on:submit="dirty = false">
                @csrf
                @method('PATCH')
                <label for="translation-value" class="ui-label">Translation</label>
                <textarea id="translation-value" name="value" rows="4" maxlength="4000" required
                    dir="{{ $language->direction }}" lang="{{ $language->code }}" class="ui-control font-preview w-full rounded-ui border border-control-border bg-surface p-3"
                    @error('value') aria-invalid="true" aria-describedby="translation-error" @enderror>{{ old('value', $entry->value) }}</textarea>
                @error('value')<p id="translation-error" class="ui-error">{{ $message }}</p>@enderror
                <x-ui.select name="status" label="Status">
                    @foreach($statuses as $status => $label)
                        <option value="{{ $status }}" @selected(old('status', $entry->status ?? 'draft') === $status)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <p class="ui-help">Translated publishes the value for active public locales. Draft and Needs review use the source fallback.</p>
                <p class="ui-help" role="status" x-cloak x-show="dirty">You have unsaved changes.</p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button type="submit" name="action" value="save" busy-on-submit loading-label="Saving…">Save changes</x-ui.button>
                    @if($entry->status)
                        <x-ui.button type="submit" name="action" value="reset" variant="outline" formnovalidate busy-on-submit loading-label="Resetting…"
                            x-on:click="if (!window.confirm('Reset this translation to the English source?')) $event.preventDefault()">Reset to source</x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>
    @endif
    @if($entries->isEmpty())
        <x-ui.empty-state title="No matching translations" description="Try clearing your search or filters." />
    @else
        <x-ui.table caption="Interface translation keys">
            <x-slot:head><tr><th scope="col">Key</th><th scope="col">English source</th><th scope="col">Status</th><th scope="col">Updated</th><th scope="col">Actions</th></tr></x-slot:head>
            @foreach($entries as $translation)
                <tr>
                    <th scope="row"><code dir="ltr">{{ $translation->identifier }}</code></th>
                    <td>{{ $translation->source }}</td>
                    <td><x-ui.badge>{{ $statuses[$translation->status] ?? 'Missing' }}</x-ui.badge></td>
                    <td>{{ $translation->updated_at ?? '—' }}</td>
                    <td><x-ui.button :href="route('admin.ui-translations.index', [...$filters, 'language' => $language->id, 'edit' => $translation->id], absolute: false)" variant="ghost">Edit</x-ui.button></td>
                </tr>
            @endforeach
        </x-ui.table>
        <x-ui.pagination :paginator="$entries" />
    @endif
</x-layouts.workspace>
