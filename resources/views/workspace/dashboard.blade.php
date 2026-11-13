@php($user = auth()->user())
<x-layouts.workspace title="Dashboard">
    <x-ui.card title="Account overview" class="max-w-3xl">
        <div class="mb-4 flex items-center gap-3 border-b border-border pb-4">
            <x-ui.avatar :name="$user->name" :src="$user->avatarUrl()" decorative />
            <div class="min-w-0">
                <p class="break-words font-medium">{{ $user->name }}</p>
                <p class="ui-help break-all">{{ $user->email }}</p>
            </div>
        </div>
        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="ui-help">Role</dt><dd class="mt-1">{{ $user->workspaceRole()->label() }}</dd></div>
            <div>
                <dt class="ui-help">Email verification</dt>
                <dd class="mt-1">
                    @if($user->hasVerifiedEmail())
                        <x-ui.badge>Verified</x-ui.badge>
                    @else
                        @can('admin.access')
                            <p class="ui-help mb-1">Verify your email to access Admin Console.</p>
                        @else
                            <p class="ui-help mb-1">Confirm you can receive email at this address.</p>
                        @endcan
                        <a class="underline underline-offset-4" href="{{ route('verification.notice', absolute: false) }}">Verify email</a>
                    @endif
                </dd>
            </div>
        </dl>
        @if($user->hasVerifiedEmail())
            <div class="mt-4 border-t border-border pt-3">
                @can('admin.access')
                    <x-ui.button :href="route('admin', absolute: false)" icon="shield" variant="outline">Open Admin Console</x-ui.button>
                @else
                    <x-ui.button :href="route('media.index', absolute: false)" icon="image" variant="outline">Manage your images</x-ui.button>
                @endcan
            </div>
        @endif
    </x-ui.card>

</x-layouts.workspace>
