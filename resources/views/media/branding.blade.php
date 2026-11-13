<x-layouts.workspace title="Global branding" description="Choose images from your library to publish as workspace branding.">
    <x-ui.card>
        <form method="post" action="{{ route('admin.branding.update') }}" class="grid gap-5">
            @csrf
            @method('PATCH')
            @foreach(['logo' => 'Site logo', 'dark-logo' => 'Dark-mode logo', 'favicon' => 'Favicon', 'social' => 'Social sharing image'] as $slot => $label)
                <x-ui.media-picker :name="$slot" :$label :value="$selected[$slot] ?? null"
                    :preview-url="isset($selected[$slot]) ? route('branding.asset', ['slot' => $slot], absolute: false) : null" />
            @endforeach
            <x-ui.button type="submit">Save branding</x-ui.button>
        </form>
    </x-ui.card>
</x-layouts.workspace>
