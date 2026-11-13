<x-workspace.profile title="Account security">
    <x-ui.card title="Verification and sessions">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.badge>{{ auth()->user()->hasVerifiedEmail() ? 'Email verified' : 'Verification pending' }}</x-ui.badge>
            @unless(auth()->user()->hasVerifiedEmail())<x-ui.button :href="route('verification.notice', absolute: false)" variant="outline">Verify email</x-ui.button>@endunless
            <x-ui.button :href="route('profile.sessions', absolute: false)" variant="ghost">Active sessions ({{ $sessionCount }})</x-ui.button>
        </div>
    </x-ui.card>
    <x-ui.card title="Change password" description="Changing your password signs out your other sessions.">
        <form method="post" action="/profile/password" class="grid max-w-xl gap-4">
            @csrf
            @method('PATCH')
            <x-ui.password-field name="current_password" label="Current password" required />
            <x-ui.password-field name="password" id="profile-password" label="New password" autocomplete="new-password" aria-describedby="profile-strength" required />
            <x-ui.password-field name="password_confirmation" id="profile-confirmation" label="Confirm new password" autocomplete="new-password" aria-describedby="profile-strength" required />
            <x-ui.password-strength id="profile-strength" password-id="profile-password" confirmation-id="profile-confirmation" />
            <x-ui.button type="submit" class="justify-self-start" busy-on-submit loading-label="Changing password…">Change password</x-ui.button>
        </form>
    </x-ui.card>
    <x-ui.card title="Recent account activity">
        @forelse($events as $event)
            <p class="ui-help py-1">{{ ucfirst(str_replace('_', ' ', substr($event->action, 8))) }} · {{ \Illuminate\Support\Carbon::parse($event->created_at)->setTimezone(auth()->user()->timezone ?? config('app.timezone'))->format('M j, Y H:i T') }}</p>
        @empty
            <p class="ui-help">No account changes recorded yet.</p>
        @endforelse
    </x-ui.card>
</x-workspace.profile>
