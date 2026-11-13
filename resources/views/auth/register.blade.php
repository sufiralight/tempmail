<x-layouts.app title="Register" mode="auth" heading="Create your account">
    @include('auth.errors')
    <x-ui.card>
        <form method="post" action="/register" class="grid gap-4">
            @csrf
            <x-ui.input id="register-name" name="name" label="Full name" :value="old('name')" autocomplete="name" maxlength="255" required />
            <x-ui.input id="register-email" name="email" type="email" label="Email address" :value="old('email')"
                autocomplete="username" maxlength="255" required />
            <x-ui.password-field id="register-password" name="password" label="Password" autocomplete="new-password"
                help="Use at least 12 characters." aria-describedby="register-strength" minlength="12" required />
            <x-ui.password-field id="register-confirmation" name="password_confirmation" label="Confirm password"
                autocomplete="new-password" aria-describedby="register-strength" minlength="12" required />
            <x-ui.password-strength id="register-strength" password-id="register-password" confirmation-id="register-confirmation" />
            <x-ui.button type="submit" busy-on-submit loading-label="Creating account...">Create account</x-ui.button>
            <a href="{{ route('login', absolute: false) }}" class="text-sm underline underline-offset-4">Sign in</a>
        </form>
    </x-ui.card>
</x-layouts.app>
