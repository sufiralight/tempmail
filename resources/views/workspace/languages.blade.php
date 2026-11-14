<x-layouts.workspace title="Localization Studio" description="Manage available languages, default locale, and language visibility across your application.">
    <div class="flex flex-wrap gap-2">
        <x-ui.badge>{{ $total }} total</x-ui.badge>
        <x-ui.badge>{{ $active }} active</x-ui.badge>
        <x-ui.badge>{{ $total - $active }} inactive</x-ui.badge>
    </div>
    <x-ui.card>
        <form method="get" action="{{ route('admin.languages.index') }}" class="grid items-end gap-3 sm:grid-cols-2 xl:grid-cols-[2fr_1fr_1fr_auto]">
            <x-ui.input name="q" type="search" label="Search languages" :value="$filters['q'] ?? null" maxlength="100" />
            <x-ui.select name="status" label="Status">
                <option value="">All statuses</option>
                @foreach(['active', 'inactive'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="direction" label="Direction">
                <option value="">All directions</option>
                @foreach(['ltr', 'rtl'] as $direction)
                    <option value="{{ $direction }}" @selected(($filters['direction'] ?? '') === $direction)>{{ strtoupper($direction) }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.button type="submit" variant="outline" icon="search">Filter</x-ui.button>
        </form>
    </x-ui.card>
    @if($languages->isEmpty())
        <x-ui.empty-state title="No matching languages" description="Try clearing your search or filters." />
    @else
        <form method="post" action="{{ route('admin.languages.bulk') }}" x-data="{ selected: [] }" class="grid gap-3">
            @csrf
            @error('languages') <p role="alert">{{ $message }}</p> @enderror
            @error('languages.*') <p role="alert">{{ $message }}</p> @enderror
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit" name="action" value="activate" variant="outline" busy-on-submit loading-label="Activating…" x-bind:disabled="selected.length === 0">Activate selected</x-ui.button>
                <x-ui.button type="submit" name="action" value="deactivate" variant="outline" busy-on-submit loading-label="Deactivating…" x-bind:disabled="selected.length === 0">Deactivate selected</x-ui.button>
            </div>
            <x-ui.table caption="Language registry">
                <x-slot:head>
                    <tr>
                        <th scope="col"><input type="checkbox" aria-label="Select all languages on this page" x-bind:checked="selected.length === {{ $languages->count() }}" x-on:change="selected = $event.target.checked ? Array.from($root.querySelectorAll('[name=&quot;languages[]&quot;]'), item => item.value) : []"></th>
                        @foreach(['Languages', 'Code', 'Direction', 'Status', 'Default', 'Homepage', 'Actions'] as $heading)
                            <th scope="col">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </x-slot:head>
                @foreach($languages as $language)
                    <tr>
                        <td><input type="checkbox" name="languages[]" value="{{ $language->id }}" x-model="selected" aria-label="Select {{ $language->name }}"></td>
                        <th scope="row"><a class="underline-offset-4 hover:underline" href="{{ route('admin.languages.show', $language->id) }}" lang="{{ $language->code }}" dir="{{ $language->direction }}">{{ $language->native_name }}</a></th>

                        <td><code>{{ $language->code }}</code></td>
                        <td>{{ strtoupper($language->direction) }}</td>
                        <td><x-ui.badge>{{ $language->is_active ? 'Active' : 'Inactive' }}</x-ui.badge></td>
                        <td>{{ $defaultId === (int) $language->id ? 'Default' : '—' }}</td>
                        <td>{{ $progress === null ? 'Schema unavailable' : ($progress[$language->id] ?? 0).' / '.$homepageTotal.' published' }}</td>
                        <td><x-ui.button :href="route('admin.languages.homepage', $language->id)" variant="ghost">Edit homepage</x-ui.button></td>
                    </tr>
                @endforeach
            </x-ui.table>
        </form>
        <x-ui.pagination :paginator="$languages" />
    @endif
</x-layouts.workspace>
