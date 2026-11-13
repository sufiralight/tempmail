<x-layouts.workspace title="Your workspace" :description="'Welcome, '.auth()->user()->name.'.'">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.card title="Account overview">
            <dl class="grid gap-3 text-sm">
                <div><dt class="ui-help">Email address</dt><dd class="break-all font-mono">{{ auth()->user()->email }}</dd></div>
                <div><dt class="ui-help">Role</dt><dd>{{ auth()->user()->workspaceRole()->label() }}</dd></div>
            </dl>
        </x-ui.card>
        <x-ui.card title="Account security">
            <p class="mb-3"><x-ui.badge>{{ auth()->user()->hasVerifiedEmail() ? 'Email verified' : 'Email verification pending' }}</x-ui.badge></p>
            @unless(auth()->user()->hasVerifiedEmail())
                <x-ui.button href="/email/verify" variant="outline">Verify email</x-ui.button>
            @endunless
        </x-ui.card>
    </div>
    <x-ui.empty-state title="Temporary inbox" description="Inbox access will appear here when email service is available." />
</x-layouts.workspace>
