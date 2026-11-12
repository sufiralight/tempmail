<x-layouts.app title="Sign in" mode="auth" heading="Welcome back" description="Sign in to your Temp Mail account.">
    @if (session('status'))
        <x-ui.alert class="mb-4" variant="success" announce>{{ session('status') }}</x-ui.alert>
    @endif
    @include('auth.errors')
    <x-ui.card>
        <form method="post" action="/login" class="grid gap-4">
            @csrf
            <x-ui.input id="login-email" name="email" type="email" label="Email address" :value="old('email')"
                autocomplete="username" maxlength="255" required />
            <x-ui.password-field id="login-password" name="password" label="Password" autocomplete="current-password" required />
            <x-ui.button type="submit" busy-on-submit loading-label="Signing in...">Sign in</x-ui.button>
            <div class="flex flex-wrap justify-between gap-3 text-sm">
                <a href="{{ route('password.request', absolute: false) }}" class="underline underline-offset-4">Forgot password?</a>
                <a href="{{ route('register', absolute: false) }}" class="underline underline-offset-4">Create account</a>
            </div>
        </form>
    </x-ui.card>
</x-layouts.app>
