<x-layouts.app :title="$title" :heading="$title" :description="$description">
    <x-slot:navigation>
        @if (request()->routeIs('admin'))
            <x-ui.button href="/dashboard" variant="ghost">Workspace</x-ui.button>
        @else
            @can('access-admin')
                <x-ui.button href="/admin" variant="ghost">Administration</x-ui.button>
            @endcan
        @endif
        <form method="post" action="/logout">
            @csrf
            <x-ui.button type="submit" variant="outline" icon="log-out">Sign out</x-ui.button>
        </form>
    </x-slot:navigation>

    @if (session('status'))
        <x-ui.alert variant="success" announce>{{ session('status') }}</x-ui.alert>
    @endif
    @if (! auth()->user()->hasVerifiedEmail())
        <x-ui.alert class="mt-4" variant="info" title="Verify your email">
            <x-ui.button href="/email/verify" variant="ghost">Open verification instructions</x-ui.button>
        </x-ui.alert>
    @endif
</x-layouts.app>
