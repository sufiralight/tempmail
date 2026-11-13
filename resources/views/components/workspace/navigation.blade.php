@props(['drawer' => false])
@php
    $user = auth()->user();
    $groups = [
        'Workspace' => [
            ['dashboard', 'Dashboard', 'monitor'],
            ['media.index', 'Media Library', 'image', 'media.*'],
        ],
        'Account' => [
            ['profile.show', 'Profile', 'user'],
            ['profile.security', 'Security', 'shield'],
            ['profile.sessions', 'Active sessions', 'monitor'],
            ['profile.preferences', 'Preferences', 'settings'],
        ],
    ];
    if (! $user->hasVerifiedEmail()) {
        $groups['Account'][] = ['verification.notice', 'Email verification', 'mail'];
    }
    if ($user->hasVerifiedEmail() && $user->can('admin.access')) {
        $groups['Administration'] = [
            ['admin', 'Admin Console', 'shield'],
            ...($user->can('users.view') ? [['admin.users', 'Users', 'user', 'admin.users.*']] : []),
            ...($user->can('roles.manage') ? [['admin.roles', 'Roles & permissions', 'shield']] : []),
            ...($user->can('settings.view') ? [['admin.settings', 'Settings', 'settings']] : []),
            ...($user->can('settings.manage') ? [['admin.branding', 'Branding', 'image']] : []),
            ...($user->can('media.audit') ? [['admin.media.index', 'Media audit', 'image', 'admin.media.*']] : []),
            ...($user->can('audit.view') ? [['admin.audits', 'Audit logs', 'info']] : []),
        ];
    }
@endphp
<nav aria-label="Workspace navigation" class="space-y-4"
    @if($drawer) @click="if ($event.target.closest('a[href]') && !$event.ctrlKey && !$event.metaKey && !$event.shiftKey && !$event.altKey) $refs.dialog.close()" @endif>
    @foreach($groups as $group => $items)
        <div class="border-t border-border pt-3">
            <p class="px-3 pb-2 text-xs font-medium text-secondary-text">{{ $group }}</p>
            <ul class="grid gap-1">
                @foreach($items as $item)
                    @php
                        [$route, $label, $icon] = $item;
                        $current = request()->routeIs($route, $item[3] ?? $route);
                    @endphp
                    <li>
                        <x-ui.button :href="route($route, absolute: false)" :icon="$icon" :variant="$current ? 'secondary' : 'ghost'"
                            :aria-current="$current ? 'page' : null" :class="$current ? 'w-full justify-start! text-start border-control-border!' : 'w-full justify-start! text-start'">
                            <span class="min-w-0 break-words">{{ $label }}</span>
                        </x-ui.button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
