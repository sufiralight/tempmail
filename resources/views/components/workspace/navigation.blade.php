<nav aria-label="Workspace navigation" class="grid gap-1">
    @php($items = [
        ['dashboard', 'Dashboard', 'dashboard.view'],
        ['verification.notice', 'Email security', 'dashboard.view'],
        ['admin', 'Overview', 'admin.access'],
        ['admin.users', 'Users', 'users.view'],
        ['admin.roles', 'Roles', 'roles.manage'],
        ['admin.settings', 'Settings', 'settings.view'],
        ['admin.audits', 'Audit logs', 'audit.view'],
    ])
    @foreach($items as [$route, $label, $permission])
        @if(auth()->user()->can($permission) && ($permission === 'dashboard.view' || auth()->user()->hasVerifiedEmail()))
            @continue($route === 'verification.notice' && auth()->user()->hasVerifiedEmail())
            @php($current = request()->routeIs($route, $route.'.*'))
            <x-ui.button :href="route($route, absolute: false)" :variant="$current ? 'secondary' : 'ghost'"
                :aria-current="$current ? 'page' : null" class="justify-start!">{{ $label }}</x-ui.button>
        @endif
    @endforeach
</nav>
