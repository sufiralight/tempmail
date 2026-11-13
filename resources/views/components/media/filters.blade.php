@props(['filters', 'picker' => false, 'admin' => false])
<form method="get" action="{{ route($picker ? 'media.picker' : ($admin ? 'admin.media.index' : 'media.index')) }}" class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-3">
    <x-ui.input name="q" label="Search images" type="search" :value="$filters['q'] ?? null" maxlength="100" />
    <x-ui.select name="type" label="File type">
        <option value="">All types</option>
        @foreach(['jpg','png','webp','gif'] as $type)<option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ strtoupper($type) }}</option>@endforeach
    </x-ui.select>
    <x-ui.select name="sort" label="Sort by">
        @foreach(['newest'=>'Newest','oldest'=>'Oldest','name'=>'Name','size'=>'Size'] as $value => $label)<option value="{{ $value }}" @selected(($filters['sort'] ?? '') === $value)>{{ $label }}</option>@endforeach
    </x-ui.select>
    @unless($picker)
        <x-ui.select name="layout" label="View"><option value="grid">Grid</option><option value="list" @selected(($filters['layout'] ?? '') === 'list')>List</option></x-ui.select>
    @endunless
    <x-ui.button type="submit" variant="outline">Apply filters</x-ui.button>
</form>
