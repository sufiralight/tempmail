<x-layouts.app title="Verify your email" mode="auth" heading="Verify your email" description="Open the verification link sent to your email address.">
    @if (session('status'))
        <x-ui.alert class="mb-4" variant="info" announce>{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card>
        <p class="ui-help mb-4">If the message has not arrived, check your spam folder or request another link.</p>
        <div class="flex flex-wrap items-center gap-3">
            <form method="post" action="{{ route('verification.send', absolute: false) }}">
                @csrf
                <x-ui.button type="submit" busy-on-submit loading-label="Sending link...">Resend verification email</x-ui.button>
            </form>
            <x-ui.button href="/dashboard" variant="ghost">Back to workspace</x-ui.button>
        </div>
    </x-ui.card>
</x-layouts.app>
