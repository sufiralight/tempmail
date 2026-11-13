<x-layouts.workspace title="Fonts" description="Manage typography and font coverage across your supported languages.">
    <x-ui.card>
        <form method="get" action="{{ route('admin.fonts.index', absolute: false) }}" class="grid items-end gap-3 sm:grid-cols-2 xl:grid-cols-[2fr_1fr_1fr_auto]">
            <x-ui.input name="q" type="search" label="Search font families" :value="$filters['q'] ?? null" maxlength="100" />
            <x-ui.select name="script" label="Writing system">
                <option value="">All writing systems</option>
                @foreach($scripts as $option)
                    <option value="{{ $option }}" @selected(($filters['script'] ?? null) === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="language" label="Preview language">
                @foreach($languages as $locale)
                    <option value="{{ $locale->id }}" @selected($locale->id === $language->id)>{{ $locale->name }} · {{ $locale->native_name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.button type="submit" variant="outline" icon="search">Apply</x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.card>
        <div x-data="fontPreview" x-on:change="update($event)">
        <h2 class="ui-section-title">{{ $language->name }} typography</h2>
        <p class="ui-help mt-1"><bdi>{{ $language->native_name }}</bdi> · <code>{{ $language->code }}</code> · {{ strtoupper($language->direction) }} · {{ ucfirst($script) }} · {{ $language->is_active ? 'Active' : 'Inactive' }}</p>
        <p class="ui-help mt-2">Coverage is based on the curated catalog. Missing glyphs use the fallback stack. Preview and public font downloads are served by Google Fonts.</p>
        <x-layouts.typography :$fonts :$stylesheet preview />
        <div x-ref="sample" class="font-preview mt-4 rounded-panel border border-border bg-page p-4" lang="{{ $language->code }}" dir="{{ $language->direction }}">
            <p class="font-preview-heading text-xl leading-relaxed">{{ config('typography.samples')[$language->code] ?? $language->native_name }}</p>
            <p class="mt-2 leading-relaxed">{{ config('typography.samples')[$language->code] ?? 'The quick brown fox jumps over the lazy dog.' }} <bdi>Temp Mail · 123 — (2026)</bdi></p>
        </div>
        <p x-ref="status" role="status" class="ui-help mt-2"></p>
        <div class="mt-5 grid gap-6 md:grid-cols-2">
            @foreach(\App\Administration\Typography::ROLES as $role)
                @php($assignment = $assignments->get($role))
                <section>
                    <h3 class="ui-card-title">{{ ucfirst($role) }} font</h3>
                    <p class="ui-help mb-3">{{ $assignment ? 'Custom assignment' : 'Inherited' }} · {{ $fonts[$role]['family'] ?? 'Geist / system' }}</p>
                    <form method="post" action="{{ route('admin.fonts.assign', [$language->id, $role], absolute: false) }}" data-font-role="{{ $role }}" class="grid gap-3">
                        @csrf
                        @method('PUT')
                        <x-ui.select name="font_family_id" :id="$role.'-family'" label="Font family">
                            <option value="">Geist / system</option>
                            @foreach($compatible as $family)
                                <option value="{{ $family->id }}" data-family="{{ $family->name }}" @selected((int) ($assignment->font_family_id ?? 0) === (int) $family->id)>{{ $family->name }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.select name="weight" :id="$role.'-weight'" label="Weight">
                            @foreach([400 => 'Regular (400)', 600 => 'Semibold (600)'] as $weight => $label)
                                <option value="{{ $weight }}" @selected($fonts[$role]['weight'] === $weight)>{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.select name="fallback" :id="$role.'-fallback'" label="Fallback stack">
                            @foreach(\App\Administration\Typography::FALLBACKS as $key => $stack)
                                <option value="{{ $key }}" @selected(($assignment->fallback ?? 'system') === $key)>{{ $stack }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.button type="submit" busy-on-submit loading-label="Saving…">Save {{ $role }} font</x-ui.button>
                    </form>
                    @if($assignment)
                        <form method="post" action="{{ route('admin.fonts.reset', [$language->id, $role], absolute: false) }}" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="ghost" busy-on-submit loading-label="Resetting…">Reset {{ $role }} to inherited</x-ui.button>
                        </form>
                    @endif
                </section>
            @endforeach
        </div>
        </div>
    </x-ui.card>

    <h2 class="ui-section-title">Font library</h2>
    @if($families->isEmpty())
        <x-ui.empty-state title="No matching font families" description="Try another search or writing system." />
    @else
        <x-ui.table caption="Google Fonts catalog">
            <x-slot:head><tr><th scope="col">Family</th><th scope="col">Coverage</th><th scope="col">Status</th><th scope="col">Actions</th></tr></x-slot:head>
            @foreach($families as $family)
                <tr>
                    <th scope="row"><details><summary class="cursor-pointer">{{ $family->name }}</summary><p class="ui-help mt-1">Google Fonts · Normal · 400, 600</p></details></th>
                    <td>{{ implode(', ', $catalog[$family->name] ?? []) }}</td>
                    <td><x-ui.badge>{{ $family->is_active ? 'Active' : 'Inactive' }}</x-ui.badge></td>
                    <td>
                        <form method="post" action="{{ route('admin.fonts.visibility', $family->id, absolute: false) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $family->is_active ? 0 : 1 }}">
                            <x-ui.button type="submit" variant="outline" busy-on-submit loading-label="Updating…">{{ $family->is_active ? 'Deactivate' : 'Activate' }}</x-ui.button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>
    @endif
</x-layouts.workspace>
