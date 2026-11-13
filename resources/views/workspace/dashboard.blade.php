@php($user = auth()->user())
<x-layouts.workspace title="Dashboard" :description="'Welcome, '.$user->name.'.'">
    <x-ui.card title="Account overview">
        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="ui-help">Email address</dt><dd class="mt-1 break-all font-mono">{{ $user->email }}</dd></div>
            <div><dt class="ui-help">Role</dt><dd class="mt-1">{{ $user->workspaceRole()->label() }}</dd></div>
            <div>
                <dt class="ui-help">Email verification</dt>
                <dd class="mt-1">
                    @if($user->hasVerifiedEmail())
                        <x-ui.badge>Verified</x-ui.badge>
                    @else
                        @can('admin.access')
                            <p class="ui-help mb-1">Verify your email to access Admin Console.</p>
                        @endcan
                        <a class="underline underline-offset-4" href="{{ route('verification.notice', absolute: false) }}">Verify email</a>
                    @endif
                </dd>
            </div>
        </dl>
    </x-ui.card>

</x-layouts.workspace>
