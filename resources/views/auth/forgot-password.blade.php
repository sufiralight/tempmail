<x-layouts.app title="Forgot password" mode="auth" heading="Reset your password" description="Enter your email address to request a password reset link.">
    @if (session('status'))
        <x-ui.alert class="mb-4" variant="info" announce>{{ session('status') }}</x-ui.alert>
    @endif
    @include('auth.errors')
    <x-ui.card>
        <form method="post" action="/forgot-password" class="grid gap-4">
            @csrf
            <x-ui.input id="recovery-email" name="email" type="email" label="Email address" :value="old('email')"
                autocomplete="username" maxlength="255" required />
            <x-ui.button type="submit" busy-on-submit loading-label="Requesting link...">Request reset link</x-ui.button>
            <a href="{{ route('login', absolute: false) }}" class="text-sm underline underline-offset-4">Back to sign in</a>
        </form>
    </x-ui.card>
</x-layouts.app>
