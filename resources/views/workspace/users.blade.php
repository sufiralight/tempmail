<x-layouts.workspace title="Users">
    <x-ui.card>
        <form method="get" action="/admin/users" class="grid items-end gap-3 sm:grid-cols-2 xl:grid-cols-[2fr_1fr_1fr_auto]">
            <x-ui.input name="q" type="search" label="Search name or email" :value="$filters['q'] ?? null" maxlength="100" />
            <x-ui.select name="role" label="Role">
                <option value="">All roles</option>
                @foreach(\App\Auth\WorkspaceRole::cases() as $role)
                    <option value="{{ $role->value }}" @selected(($filters['role'] ?? null) === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="status" label="Status">
                <option value="">All statuses</option>
                <option value="active" @selected(($filters['status'] ?? null) === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Inactive</option>
            </x-ui.select>
            <x-ui.button type="submit" variant="outline" icon="search">Filter</x-ui.button>
        </form>
    </x-ui.card>
    @if($users->isEmpty())
        <x-ui.empty-state title="No matching accounts" description="Try clearing your search or filters." />
    @else
        <x-ui.table caption="Workspace users">
            <x-slot:head><tr><th scope="col">Account</th><th scope="col">Role</th><th scope="col">Status</th></tr></x-slot:head>
            @foreach($users as $account)
                <tr>
                    <td><a class="font-medium underline underline-offset-4" href="{{ route('admin.users.show', $account, absolute: false) }}">{{ $account->name }}</a><div class="ui-help break-all">{{ $account->email }}</div></td>
                    <td>{{ $account->workspaceRole()->label() }}</td>
                    <td><x-ui.badge>{{ $account->is_active ? 'Active' : 'Inactive' }}</x-ui.badge></td>
                </tr>
            @endforeach
        </x-ui.table>
        <x-ui.pagination :paginator="$users" />
    @endif
</x-layouts.workspace>
