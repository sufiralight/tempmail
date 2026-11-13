<x-workspace.profile title="Preferences" description="Choose your appearance, language and time zone.">
    <x-ui.card>
        <form method="post" action="/profile/preferences" class="grid max-w-xl gap-4">
            @csrf
            @method('PATCH')
            <x-ui.select name="appearance" label="Appearance" required>
                @foreach(['system', 'light', 'dark'] as $appearance)
                    <option value="{{ $appearance }}" @selected(old('appearance', auth()->user()->appearance ?? 'system') === $appearance)>{{ ucfirst($appearance) }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="interface_locale" label="Interface language" required><option value="en">English</option></x-ui.select>
            <x-ui.select name="timezone" label="Time zone" required>
                @foreach(DateTimeZone::listIdentifiers() as $zone)
                    <option value="{{ $zone }}" @selected(old('timezone', auth()->user()->timezone ?? config('app.timezone')) === $zone)>{{ $zone }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.button type="submit" class="justify-self-start" busy-on-submit loading-label="Saving preferences…">Save preferences</x-ui.button>
        </form>
    </x-ui.card>
</x-workspace.profile>
