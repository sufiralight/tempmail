<x-layouts.app :title="$title" mode="installer" class="max-w-xl!">
    <x-ui.card>
        <h1 class="ui-section-title">{{ $title }}</h1>
        <p class="ui-help mt-2">{{ $message }}</p>
        <x-ui.button href="/install" variant="outline" class="mt-5">Return to setup</x-ui.button>
    </x-ui.card>
</x-layouts.app>
