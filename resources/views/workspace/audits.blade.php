<x-layouts.workspace title="Audit logs" description="Administrative changes and protection events.">
    <x-ui.card>
        <form method="get" action="/admin/audit-logs" class="flex flex-wrap items-end gap-3">
            <x-ui.select name="action" label="Action">
                <option value="">All actions</option>
                @foreach(\App\Administration\WorkspaceAdministration::ACTIONS as $event)
                    <option value="{{ $event }}" @selected($action === $event)>{{ $event }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.button type="submit" variant="outline">Filter</x-ui.button>
        </form>
    </x-ui.card>
    @if($entries->isEmpty())
        <x-ui.empty-state title="No matching events" description="Administrative changes will appear here." />
    @else
        <x-ui.table caption="Administrative audit events">
            <x-slot:head><tr><th scope="col">Time</th><th scope="col">Action</th><th scope="col">Actor</th><th scope="col">Target</th><th scope="col">Details</th></tr></x-slot:head>
            @foreach($entries as $entry)
                <tr>
                    <td class="whitespace-nowrap">{{ $entry->created_at }}</td>
                    <td>{{ $entry->action }}</td><td>#{{ $entry->actor_id }}</td>
                    <td>{{ $entry->target_type }} #{{ $entry->target_id }}</td>
                    <td class="font-mono text-xs">{{ $entry->metadata }}</td>
                </tr>
            @endforeach
        </x-ui.table>
        <x-ui.pagination :paginator="$entries" />
    @endif
</x-layouts.workspace>
