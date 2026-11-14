@php
    $roles = \App\Auth\WorkspaceRole::cases();
    $groups = [
        'Workspace' => ['View dashboard' => 'dashboard.view', 'Manage own profile and media' => null],
        'Users' => ['View users' => 'users.view', 'Manage users and role assignments' => 'users.manage'],
        'Administration' => ['Open Admin Console' => 'admin.access', 'View settings' => 'settings.view', 'Manage settings and branding' => 'settings.manage', 'View roles and permissions' => 'roles.manage'],
        'Security & Audit' => ['View audit logs' => 'audit.view', 'Access media audit' => 'media.audit'],
        'Localization' => ['Manage languages' => 'settings.manage', 'Manage fonts' => 'settings.manage', 'Manage UI translations' => 'settings.manage'],
    ];
@endphp

<x-layouts.workspace title="Roles & permissions" description="Compare the permissions of the built-in workspace roles.">
    <x-workspace.users-navigation />
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="ui-help">System roles have fixed permissions. Change assignments from an account’s details.</p>
        @can('users.view')
            <x-ui.button :href="route('admin.users', absolute: false)" variant="outline" icon="user">Manage users</x-ui.button>
        @endcan
    </div>

    <ul class="grid gap-3 sm:grid-cols-3" aria-label="System roles">
        @foreach($roles as $role)
            @php($count = $counts[$role->value] ?? 0)
            <li class="rounded-panel border border-border bg-surface p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="ui-card-title">{{ $role->label() }}</h2>
                    <x-ui.badge>System role</x-ui.badge>
                </div>
                <p class="ui-help mt-1">{{ $role->description() }}</p>
                <p class="mt-3 text-sm font-medium tabular-nums">{{ $count }} {{ $count === 1 ? 'account' : 'accounts' }}</p>
            </li>
        @endforeach
    </ul>

    <section aria-labelledby="capabilities">
        <h2 id="capabilities" class="ui-section-title">Permission matrix</h2>
        <p class="ui-help mt-1 mb-3">Account counts include inactive accounts. Console access also requires an active account and verified email. User changes are subject to account protection rules.</p>
        <x-ui.table caption="Permission matrix">
            <x-slot:head>
                <tr>
                    <th scope="col" class="w-2/5">Capability</th>
                    @foreach($roles as $role)
                        <th scope="col" class="text-center!">{{ $role->label() }}</th>
                    @endforeach
                </tr>
            </x-slot:head>
            @foreach($groups as $group => $capabilities)
                <tr><th colspan="4" class="bg-muted text-xs font-semibold">{{ $group }}</th></tr>
                @foreach($capabilities as $label => $permission)
                    <tr>
                        <th scope="row" class="font-medium wrap-anywhere">{{ $label }}</th>
                        @foreach($roles as $role)
                            @php($granted = $permission === null || in_array($permission, $role->permissions(), true))
                            <td class="text-center!">
                                @if($granted)
                                    <span class="inline-flex"><x-ui.icon name="check" :size="16" /><span class="sr-only">Granted</span></span>
                                @else
                                    <span aria-hidden="true" class="text-secondary-text">−</span><span class="sr-only">Denied</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </x-ui.table>
    </section>
</x-layouts.workspace>
