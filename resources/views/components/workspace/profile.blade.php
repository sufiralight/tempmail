@props(['title', 'description' => null])
<x-layouts.workspace :$title :$description>
    <div class="max-w-3xl space-y-4">{{ $slot }}</div>
</x-layouts.workspace>
