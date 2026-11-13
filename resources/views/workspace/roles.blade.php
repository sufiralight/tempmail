<x-layouts.workspace title="Roles and permissions" description="System roles have fixed permissions. Change role assignments from an account’s details.">
    <div class="grid gap-4 xl:grid-cols-3">
        @foreach(\App\Auth\WorkspaceRole::cases() as $role)
            <x-ui.card :title="$role->label()" :description="$role->description()">
                <p class="mb-3 text-sm">{{ $counts[$role->value] ?? 0 }} accounts</p>
                <ul class="grid gap-1 font-mono text-sm">
                    @foreach($role->permissions() as $permission)<li>{{ $permission }}</li>@endforeach
                </ul>
            </x-ui.card>
        @endforeach
    </div>
</x-layouts.workspace>
