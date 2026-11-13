<x-layouts.workspace title="Administration" :description="$workspaceName">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.card title="Accounts"><p class="text-3xl font-semibold tabular-nums">{{ $accounts }}</p></x-ui.card>
        <x-ui.card title="Verified email addresses"><p class="text-3xl font-semibold tabular-nums">{{ $verified }}</p></x-ui.card>
    </div>
</x-layouts.workspace>
