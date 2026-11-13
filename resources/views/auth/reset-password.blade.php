<x-layouts.app title="New password" mode="auth" heading="Set a new password">
    @include('auth.errors')
    <x-ui.card>
        <form method="post" action="/reset-password" class="grid gap-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-ui.input id="reset-email" name="email" type="email" label="Email address" :value="old('email', request()->query('email'))"
                autocomplete="username" maxlength="255" required />
            <x-ui.password-field id="reset-password" name="password" label="New password" autocomplete="new-password"
                help="Use at least 12 characters." aria-describedby="reset-strength" minlength="12" required />
            <x-ui.password-field id="reset-confirmation" name="password_confirmation" label="Confirm password"
                autocomplete="new-password" aria-describedby="reset-strength" minlength="12" required />
            <x-ui.password-strength id="reset-strength" password-id="reset-password" confirmation-id="reset-confirmation" />
            <x-ui.button type="submit" busy-on-submit loading-label="Saving password...">Save new password</x-ui.button>
        </form>
    </x-ui.card>
</x-layouts.app>
