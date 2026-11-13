<x-layouts.app title="Create your administrator account" mode="installer" class="max-w-xl!">
    <div class="mb-6">
        <p class="ui-help mb-2">Workspace setup</p>
        <h1 class="ui-page-title">Create your administrator account</h1>
        <p class="ui-help mt-2">Set up the first Super Admin account for your workspace.</p>
    </div>

    <x-installation.steps :step="2" verified />
    @if ($errors->any())
        <x-ui.alert tabindex="-1" x-data x-init="$el.focus()" class="mb-4"
            variant="error" title="Check your account details" announce>
            Review the fields below and enter the account details again.
            <x-ui.form-error :message="$errors->first('role')" />
            <x-ui.form-error :message="$errors->first('is_super_admin')" />
        </x-ui.alert>
    @else
        <x-ui.alert class="mb-4" variant="success" title="Database connection verified" />
    @endif

    <x-ui.card>
        <form method="post" action="/install/admin" class="grid gap-4">
            @csrf
            <x-ui.input name="name" id="admin-name" label="Full name" autocomplete="name" maxlength="255" required />
            <x-ui.input name="email" id="admin-email" type="email" label="Email address" autocomplete="username" maxlength="255" required />
            <x-ui.password-field name="password" id="admin-password" label="Password" autocomplete="new-password"
                help="Use at least 12 characters." minlength="12" required />
            <x-ui.password-field name="password_confirmation" id="admin-password-confirmation" label="Confirm password"
                autocomplete="new-password" minlength="12" required />
            <div class="flex flex-wrap items-center justify-end gap-3">
            @if($reversible)<x-ui.button href="/install" variant="outline" size="lg" icon="arrow-left" class="me-auto">Back</x-ui.button>@endif
            <x-ui.button type="submit" size="lg" busy-on-submit loading-label="Finishing workspace setup...">
                Finish workspace setup
            </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-slot:footer><p class="ui-help">Step 2 of 2 · Administrator account</p></x-slot:footer>
</x-layouts.app>
