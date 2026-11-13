@props(['title', 'description' => null])
<x-layouts.app :$title :heading="$title" :$description>
    <x-slot:navigation>
        <div class="lg:hidden">
            <x-ui.modal title="Workspace navigation" drawer>
                <x-slot:trigger><x-ui.button variant="outline" icon="menu" aria-label="Open workspace navigation" /></x-slot:trigger>
                <x-workspace.navigation />
            </x-ui.modal>
        </div>
        <form method="post" action="/logout">
            @csrf
            <x-ui.button type="submit" variant="outline" icon="log-out">Sign out</x-ui.button>
        </form>
    </x-slot:navigation>
    <x-slot:breadcrumbs>
        <a class="underline underline-offset-4" href="/dashboard">Workspace</a> <span aria-hidden="true">/</span> {{ $title }}
    </x-slot:breadcrumbs>
    <div class="flex items-start gap-6">
        <aside class="hidden shrink-0 lg:block">
            <details open class="ui-card">
                <summary class="cursor-pointer text-sm font-semibold">Navigation</summary>
                <div class="mt-3 w-40"><x-workspace.navigation /></div>
            </details>
        </aside>
        <div class="min-w-0 flex-1 space-y-4">
            @if(session('status'))<x-ui.alert variant="success" announce>{{ session('status') }}</x-ui.alert>@endif
            @if($errors->any())
                <x-ui.alert variant="error" title="Review the submitted changes" announce tabindex="-1" x-data x-init="$el.focus()">
                    <x-ui.form-error :message="$errors->first()" />
                </x-ui.alert>
            @endif
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
