<x-layouts.app title="Design system" heading="Design system" description="Preview the shared Temp Mail controls in light, dark, and system themes.">
    <x-slot:breadcrumbs><a class="underline underline-offset-4" href="{{ url('/') }}">Home</a> <span aria-hidden="true">/</span> Design system</x-slot:breadcrumbs>

    <div class="grid items-start gap-5 lg:grid-cols-2">
        <x-ui.card title="Form controls" description="Labels, help text, and validation share the same field layout.">
            <form class="grid gap-4" x-data="{ saved: false }" @submit.prevent="saved = true">
                <x-ui.input type="email" name="email" label="Email address" placeholder="you@example.com" leading-icon="mail" required />
                <x-ui.password-field id="preview-password" name="password" label="Password" help="Use a unique password." autocomplete="new-password" />
                <x-ui.input name="display_name" label="Display name" error="Enter a display name." />
                <x-ui.select name="format" label="Format"><option>Plain text</option><option>HTML</option></x-ui.select>
                <x-ui.textarea name="note" label="Notes" placeholder="Add a note" />
                <fieldset class="flex flex-wrap gap-x-4">
                    <legend class="ui-label">Delivery</legend>
                    <x-ui.radio name="delivery" value="daily" checked label="Daily" />
                    <x-ui.radio name="delivery" value="weekly" label="Weekly" />
                </fieldset>
                <x-ui.checkbox name="updates" label="Receive updates" />
                <div class="flex flex-wrap gap-2">
                    <x-ui.button type="submit" busy-on-submit>Save example</x-ui.button>
                    <x-ui.button disabled variant="outline">Unavailable</x-ui.button>
                </div>
                <p class="ui-help" role="status" x-text="saved ? 'Example submitted in this browser.' : ''"></p>
            </form>
        </x-ui.card>

        <div class="grid gap-5">
            <x-ui.card title="Actions" x-data="{ feedback: '' }">
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" variant="secondary" @click="feedback = 'Secondary action selected.'">Secondary</x-ui.button>
                    <x-ui.dropdown label="Actions" align="start">
                        <button type="button" role="menuitem" tabindex="-1" class="ui-menu-item" @click="feedback = 'Details: this is a local component preview.'">View details</button>
                        <button type="button" role="menuitem" tabindex="-1" class="ui-menu-item" disabled>Unavailable</button>
                        <button type="button" role="menuitem" tabindex="-1" class="ui-menu-item"
                            @click="navigator.clipboard?.writeText('Temp Mail').then(() => feedback = 'Label copied.', () => feedback = 'Clipboard access is unavailable.')">Copy label</button>
                    </x-ui.dropdown>
                    <x-ui.modal title="Remove image?" description="Review the confirmation and choose an action.">
                        <x-slot:trigger><x-ui.button variant="outline">Open dialog</x-ui.button></x-slot:trigger>
                        <p class="ui-help">Check the image and its references before confirming removal.</p>
                        <x-slot:footer>
                            <form method="dialog" class="flex flex-wrap gap-2">
                                <x-ui.button type="submit" variant="outline" autofocus>Cancel</x-ui.button>
                                <x-ui.button type="submit" variant="destructive" icon="trash" @click="feedback = 'Example removal confirmed.'">Confirm removal</x-ui.button>
                            </form>
                        </x-slot:footer>
                    </x-ui.modal>
                    <x-ui.tooltip id="search-tip" text="Search example records">
                        <x-ui.button variant="ghost" icon="search" aria-label="Search" aria-describedby="search-tip" @click="feedback = 'Search is scoped to the example records.'" />
                    </x-ui.tooltip>
                </div>
                <p class="ui-help mt-2" role="status" x-text="feedback"></p>
            </x-ui.card>

            <x-ui.card title="Feedback">
                <div class="grid gap-3">
                    <x-ui.alert variant="success" title="Example saved">Your changes are ready to review.</x-ui.alert>
                    <x-ui.toast title="Preview notification">Dismiss this message to test the control.</x-ui.toast>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.avatar name="Temp Mail" />
                        <x-ui.badge variant="outline">Draft</x-ui.badge>
                        <x-ui.spinner label="Loading example" />
                        <x-ui.skeleton class="w-20" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Media and records">
                <x-ui.tabs :tabs="['media' => 'Media', 'records' => 'Records']" label="Content examples">
                    <x-slot:media>
                        <x-ui.file-upload name="image" label="Image" help="Select a PNG or JPEG to test the browser file picker." accept="image/png,image/jpeg" :progress="35" />
                        <x-ui.empty-state class="mt-4" title="No images uploaded" description="The preview leaves selected files in your browser." icon="image" />
                    </x-slot:media>
                    <x-slot:records>
                        <x-ui.table caption="Example records">
                            <x-slot:head><tr><th scope="col">Name</th><th scope="col">Status</th></tr></x-slot:head>
                            <tr><td class="font-mono">example.png</td><td><x-ui.badge>Ready</x-ui.badge></td></tr>
                        </x-ui.table>
                    </x-slot:records>
                </x-ui.tabs>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
