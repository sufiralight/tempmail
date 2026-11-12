<x-layouts.app title="Connect your database" mode="installer" class="max-w-xl!">
    <div class="mb-6">
        <p class="ui-help mb-2">Workspace setup</p>
        <h1 class="ui-page-title">Connect your database</h1>
        <p class="ui-help mt-2">Enter your MySQL database details to configure your workspace.</p>
    </div>

    @if ($verified)
        <x-ui.alert id="connection-verified" tabindex="-1" x-data x-init="$el.focus()" class="mb-4"
            variant="success" title="Database connection verified" announce>
            <p>Continue to administrator setup before this verification expires.</p>
            <x-ui.button href="/install/admin" variant="outline" class="mt-2">Continue to administrator setup</x-ui.button>
        </x-ui.alert>
    @endif

    @if ($expired)
        <x-ui.alert class="mb-4" variant="info" title="Verification expired" announce>
            Verify the database again to continue.
        </x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert tabindex="-1" x-data x-init="$el.focus()" class="mb-4"
            variant="error" title="{{ $errors->has('connection') ? 'Connection could not be verified' : 'Check your database details' }}" announce>
            {{ $errors->first('connection') ?: 'Review the fields below and enter the connection details again.' }}
        </x-ui.alert>
    @endif

    @unless ($pendingFinalization)
    <x-ui.card>
        <form method="post" action="/install" class="grid gap-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-[1fr_8rem]">
                <x-ui.input name="host" id="db-host" label="Database host" :value="$access['host']"
                    autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="253" required />
                <x-ui.input name="port" id="db-port" label="Database port" type="number" :value="$access['port']"
                    min="1" max="65535" inputmode="numeric" required />
            </div>
            <x-ui.input name="database" id="db-name" label="Database name" maxlength="64"
                help="Use an empty database created for this installation." autocomplete="off" autocapitalize="off" required />
            <x-ui.input name="username" id="db-username" label="Database username" maxlength="80"
                autocomplete="off" autocapitalize="off" required />
            <x-ui.password-field name="database_password" id="db-password" label="Database password" maxlength="512"
                autocomplete="off" :help="$access['local'] ? 'Leave blank if the local database account has no password.' : null"
                :required="! $access['local']" />
            <x-ui.button type="submit" size="lg" busy-on-submit loading-label="Verifying connection...">
                {{ $verified ? 'Verify another connection' : 'Verify connection' }}
            </x-ui.button>
        </form>
    </x-ui.card>
    @endunless

    <x-slot:footer><p class="ui-help">Step 1 of 2 · Database connection</p></x-slot:footer>
</x-layouts.app>
