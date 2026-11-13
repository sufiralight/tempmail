@php($user = auth()->user())
<x-layouts.workspace title="Dashboard" :description="'Welcome, '.$user->name.'.'">
    <x-ui.card title="Account overview">
        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="ui-help">Email address</dt><dd class="mt-1 break-all font-mono">{{ $user->email }}</dd></div>
            <div><dt class="ui-help">Role</dt><dd class="mt-1">{{ $user->workspaceRole()->label() }}</dd></div>
            <div>
                <dt class="ui-help">Email verification</dt>
                <dd class="mt-1">
                    @if($user->hasVerifiedEmail())
                        <x-ui.badge>Verified</x-ui.badge>
                    @else
                        <a class="underline underline-offset-4" href="{{ route('verification.notice', absolute: false) }}">Verify email</a>
                    @endif
                </dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Quick actions">
        <div class="grid gap-2 sm:grid-cols-2">
            <x-ui.button :href="route('profile.show', absolute: false)" icon="user" variant="outline" class="justify-start!">Manage profile</x-ui.button>
            <x-ui.button :href="route('profile.security', absolute: false)" icon="shield" variant="outline" class="justify-start!">Account security</x-ui.button>
            <x-ui.button :href="route('media.index', absolute: false)" icon="image" variant="outline" class="justify-start!">Media Library</x-ui.button>
            @if($user->hasVerifiedEmail() && $user->can('admin.access'))
                <x-ui.button :href="route('admin', absolute: false)" icon="settings" variant="outline" class="justify-start!">Admin Console</x-ui.button>
            @endif
        </div>
    </x-ui.card>
</x-layouts.workspace>
