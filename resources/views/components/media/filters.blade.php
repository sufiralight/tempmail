@props(['filters', 'picker' => false, 'admin' => false, 'trash' => false])
<form method="get" action="{{ route($picker ? 'media.picker' : ($admin ? 'admin.media.index' : ($trash ? 'media.trash' : 'media.index'))) }}" class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
    @if($picker && !empty($filters['avatar']))<input type="hidden" name="avatar" value="1">@endif
    <x-ui.input name="q" label="Search images" type="search" :value="$filters['q'] ?? null" maxlength="100" />
    <x-ui.select name="type" label="File type">
        <option value="">All types</option>
        @foreach(['jpg','png','webp','gif'] as $type)<option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ strtoupper($type) }}</option>@endforeach
    </x-ui.select>
    <x-ui.select name="sort" label="Sort by">
        @foreach(['newest'=>'Newest','oldest'=>'Oldest','name'=>'Name A–Z','name_desc'=>'Name Z–A','size'=>'Size'] as $value => $label)<option value="{{ $value }}" @selected(($filters['sort'] ?? '') === $value)>{{ $label }}</option>@endforeach
    </x-ui.select>
    @unless($picker)
        <input type="hidden" name="layout" value="{{ $filters['layout'] ?? 'grid' }}">
        <x-ui.select name="per_page" label="Per page">
            @foreach([20, 40, 80, 100] as $size)
                <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 20) === $size)>{{ $size }}</option>
            @endforeach
        </x-ui.select>
    @endunless
    <x-ui.button type="submit" variant="outline">Apply filters</x-ui.button>
</form>
