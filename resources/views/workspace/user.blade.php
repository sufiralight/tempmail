<x-layouts.workspace title="Account details">
    <x-ui.card :title="$account->name">
        <x-ui.avatar :name="$account->name" :src="$account->avatarUrl(admin: true)" size="lg" decorative class="mb-3" />
        <dl class="grid gap-2 text-sm">
            <div class="break-all"><dt class="ui-help">Email</dt><dd>{{ $account->email }}</dd></div>
            <div><dt class="ui-help">Role and status</dt><dd>{{ $account->workspaceRole()->label() }} · {{ $account->is_active ? 'Active' : 'Inactive' }}</dd></div>
            <div><dt class="ui-help">Verification</dt><dd>{{ $account->hasVerifiedEmail() ? 'Verified' : 'Pending' }}</dd></div>
        </dl>
    </x-ui.card>
    @can('manage', $account)
        <x-ui.card title="Account access">
            <form method="post" action="{{ route('admin.users.update', $account, absolute: false) }}" class="grid gap-4">
                @csrf
                @method('PATCH')
                <x-ui.select name="role" label="Role" required>
                    @foreach(\App\Auth\WorkspaceRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected($account->workspaceRole() === $role)>{{ $role->label() }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="active" label="Account status" required>
                    <option value="1" @selected($account->is_active)>Active</option>
                    <option value="0" @selected(!$account->is_active)>Inactive</option>
                </x-ui.select>
                <x-ui.checkbox name="confirmed" required label="I confirm this change to account access." />
                <x-ui.button type="submit" busy-on-submit>Save access</x-ui.button>
            </form>
        </x-ui.card>
    @endcan
</x-layouts.workspace>
