<x-workspace.profile title="Active sessions" description="Review browser sessions and sign out devices you no longer use.">
    <x-ui.card>
        <ul class="divide-y divide-border">
            @foreach($sessions as $entry)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div>
                        <p class="font-medium">{{ $entry->device }} @if($entry->current_device)<x-ui.badge>Current session</x-ui.badge>@endif</p>
                        <p class="ui-help">{{ \Illuminate\Support\Carbon::createFromTimestamp($entry->last_seen)->setTimezone(auth()->user()->timezone ?? config('app.timezone'))->format('M j, Y H:i T') }}</p>
                    </div>
                    @unless($entry->current_device)
                        <x-ui.modal title="Sign out this session?">
                            <x-slot:trigger><x-ui.button variant="outline">Sign out session</x-ui.button></x-slot:trigger>
                            <x-profile.revoke-form :action="route('profile.sessions.revoke', $entry->id, absolute: false)" single />
                        </x-ui.modal>
                    @endunless
                </li>
            @endforeach
        </ul>
        <x-ui.pagination :paginator="$sessions" />
        <x-ui.modal title="Sign out other sessions?" class="mt-4">
            <x-slot:trigger><x-ui.button variant="outline">Sign out other sessions</x-ui.button></x-slot:trigger>
            <x-profile.revoke-form action="/profile/sessions/revoke-others" />
        </x-ui.modal>
    </x-ui.card>
</x-workspace.profile>
