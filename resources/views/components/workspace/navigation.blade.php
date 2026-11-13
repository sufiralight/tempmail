<nav aria-label="Workspace navigation" class="grid gap-1">
    @php($items = [
        ['dashboard', 'Dashboard', 'dashboard.view'],
        ['profile.show', 'Profile', 'dashboard.view'],
        ['profile.security', 'Security', 'dashboard.view'],
        [request()->routeIs('admin', 'admin.*') && auth()->user()->can('media.audit') ? 'admin.media.index' : 'media.index', 'Media Library', 'dashboard.view'],
        ['verification.notice', 'Email security', 'dashboard.view'],
        ['admin', 'Overview', 'admin.access'],
        ['admin.users', 'Users', 'users.view'],
        ['admin.roles', 'Roles', 'roles.manage'],
        ['admin.settings', 'Settings', 'settings.view'],
        ['admin.branding', 'Branding', 'settings.manage'],
        ['admin.audits', 'Audit logs', 'audit.view'],
    ])
    @foreach($items as [$route, $label, $permission])
        @if(auth()->user()->can($permission) && ($permission === 'dashboard.view' || auth()->user()->hasVerifiedEmail()))
            @continue($route === 'verification.notice' && auth()->user()->hasVerifiedEmail())
            @php($current = match ($route) {
                'admin' => request()->routeIs('admin'),
                'media.index' => request()->routeIs('media.*'),
                'admin.media.index' => request()->routeIs('admin.media.*'),
                default => request()->routeIs($route, $route.'.*'),
            })
            <x-ui.button :href="route($route, absolute: false)" :variant="$current ? 'secondary' : 'ghost'"
                :aria-current="$current ? 'page' : null" class="justify-start!">{{ $label }}</x-ui.button>
        @endif
    @endforeach
</nav>
