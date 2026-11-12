<x-layouts.app title="Connect your database" mode="installer" class="max-w-xl!">
    <div class="mb-6">
        <p class="ui-help mb-2">Workspace setup</p>
        <h1 class="ui-page-title">Connect your database</h1>
        <p class="ui-help mt-2">Choose a database engine to configure your workspace.</p>
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
        @php
            $options = collect($engines)->mapWithKeys(fn ($engine) => [$engine->value => [
                'port' => $access['local'] ? $engine->port() : $access['port'],
                'enabled' => $engine->available() && ($access['local'] || $access['engine'] === $engine->value),
            ]]);
        @endphp
        <form method="post" action="/install" class="grid gap-4"
            x-data="{ engine: @js($initial->value), port: @js($options[$initial->value]['port']), options: @js($options) }">
            @csrf
            <x-ui.select name="engine" id="db-engine" label="Database engine" x-model="engine" x-on:change="port = options[$event.target.value].port" required>
                @foreach ($engines as $engine)
                    <option value="{{ $engine->value }}" @selected($initial === $engine) @disabled(! $options[$engine->value]['enabled'])>
                        {{ $engine->label() }}{{ ! $engine->available() ? ' · requires '.$engine->extension() : (! $options[$engine->value]['enabled'] ? ' · outside deployment configuration' : '') }}
                    </option>
                @endforeach
            </x-ui.select>
            <p class="ui-help" x-cloak x-show="engine === 'sqlsrv'">SQL Server requires PDO SQLSRV, a compatible Microsoft ODBC driver, and a trusted TLS certificate with the default settings.</p>
            @if (! $initial->available())
                <x-ui.alert variant="warning" title="Database driver unavailable">
                    Enable {{ $initial->extension() }} on the hosting server. SQL Server also requires a compatible Microsoft ODBC driver.
                </x-ui.alert>
            @endif
            <fieldset class="grid gap-4" x-show="engine !== 'sqlite'" :disabled="engine === 'sqlite'" @disabled($initial->value === 'sqlite')>
            <div class="grid gap-4 sm:grid-cols-[1fr_8rem]">
                <x-ui.input name="host" id="db-host" label="Database host" :value="$access['host']"
                    autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="253" required />
                <x-ui.input name="port" id="db-port" label="Database port" type="number" :value="$options[$initial->value]['port']" x-model="port"
                    min="1" max="65535" inputmode="numeric" required />
            </div>
            <x-ui.input name="database" id="db-name" label="Database name" maxlength="64"
                help="Use an empty database created for this installation." autocomplete="off" autocapitalize="off" required />
            <x-ui.input name="username" id="db-username" label="Database username" maxlength="80"
                autocomplete="off" autocapitalize="off" required />
            <x-ui.password-field name="database_password" id="db-password" label="Database password" maxlength="512"
                autocomplete="off" :help="$access['local'] ? 'Leave blank if the local database account has no password.' : null"
                :required="! $access['local']" />
            </fieldset>
            <p class="ui-help" x-cloak x-show="engine === 'sqlite'">Setup will create a new database in private application storage after you finish. No file path is needed.</p>
            <x-ui.button type="submit" size="lg" busy-on-submit loading-label="Verifying connection..."
                :disabled="! $options[$initial->value]['enabled']" ::disabled="!options[engine]?.enabled">
                {{ $verified ? 'Verify another connection' : 'Verify connection' }}
            </x-ui.button>
        </form>
    </x-ui.card>
    @endunless

    <x-slot:footer><p class="ui-help">Step 1 of 2 · Database connection</p></x-slot:footer>
</x-layouts.app>
