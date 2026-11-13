<x-layouts.workspace title="Settings">
    <x-ui.card title="General">
        @can('settings.manage')
            <form method="post" action="/admin/settings" class="grid gap-4">
                @csrf
                @method('PATCH')
                <x-ui.input name="display_name" label="Workspace display name" :value="$name" maxlength="80" required />
                <x-ui.button type="submit" busy-on-submit class="justify-self-start" loading-label="Saving settings…">Save settings</x-ui.button>
            </form>
        @else
            <p>{{ $name }}</p>
        @endcan
    </x-ui.card>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.card title="Branding"><p class="ui-help">The workspace uses the Temp Mail logo and Zinc theme.</p></x-ui.card>
        <x-ui.card title="Security"><p class="ui-help">Console access requires a verified email address and an administrative role.</p></x-ui.card>
        <x-ui.card title="Authentication"><p class="ui-help">Members sign in with their email address and password.</p></x-ui.card>
        <x-ui.card title="System information"><p class="ui-help">Laravel {{ app()->version() }} · PHP {{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}</p></x-ui.card>
    </div>
</x-layouts.workspace>
