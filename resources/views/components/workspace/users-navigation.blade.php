<nav aria-label="User management" class="flex flex-wrap gap-1 border-b border-border pb-2">
    @can('users.view')
        <x-ui.button :href="route('admin.users', absolute: false)" size="sm"
            :variant="request()->routeIs('admin.users', 'admin.users.*') ? 'secondary' : 'ghost'"
            :aria-current="request()->routeIs('admin.users') ? 'page' : null">All users</x-ui.button>
    @endcan
    @can('roles.manage')
        <x-ui.button :href="route('admin.roles', absolute: false)" size="sm"
            :variant="request()->routeIs('admin.roles') ? 'secondary' : 'ghost'"
            :aria-current="request()->routeIs('admin.roles') ? 'page' : null">Roles &amp; permissions</x-ui.button>
    @endcan
</nav>
