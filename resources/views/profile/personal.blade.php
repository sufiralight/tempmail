<x-workspace.profile title="Personal information" description="Manage the name and email address on your account.">
    <x-ui.card>
        <form method="post" action="/profile" class="grid max-w-xl gap-4">
            @csrf
            @method('PATCH')
            <x-ui.input name="name" label="Full name" :value="old('name', auth()->user()->name)" autocomplete="name" maxlength="255" required />
            <x-ui.input name="email" label="Email address" type="email" :value="old('email', auth()->user()->email)" autocomplete="username" maxlength="255" required />
            <x-ui.password-field name="current_password" label="Current password" help="Required to change your email address." />
            <x-ui.button type="submit" class="justify-self-start" busy-on-submit>Save profile</x-ui.button>
        </form>
    </x-ui.card>
</x-workspace.profile>
