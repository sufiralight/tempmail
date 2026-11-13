@props(['title', 'description' => null])
@php($user = auth()->user())
<x-layouts.app :$title :heading="$title" :$description>
    <x-slot:sidebar><x-workspace.navigation /></x-slot:sidebar>
    <x-slot:context>
        <div class="flex min-w-0 items-center gap-2">
            <x-ui.modal title="Workspace navigation" drawer class="shrink-0 lg:hidden">
                <x-slot:trigger><x-ui.button variant="outline" size="sm" icon="menu" aria-label="Open workspace navigation" /></x-slot:trigger>
                <x-workspace.navigation drawer />
            </x-ui.modal>
            <span class="truncate text-sm font-medium">{{ request()->routeIs('admin', 'admin.*') ? 'Administration' : 'Workspace' }}</span>
        </div>
    </x-slot:context>
    <x-slot:account>
        <x-ui.dropdown :label="'Account menu for '.$user->name">
            <x-slot:trigger>
                <x-ui.avatar :name="$user->name" size="sm" decorative />
                <x-ui.icon name="chevron-down" :size="14" />
            </x-slot:trigger>
            <div role="presentation" class="mb-1 w-52 max-w-[calc(100vw-2rem)] border-b border-border px-2 py-2">
                <p class="break-words text-sm font-medium">{{ $user->name }}</p>
                <p class="break-all text-xs text-secondary-text">{{ $user->email }}</p>
            </div>
            <a href="{{ route('profile.show', absolute: false) }}" role="menuitem" tabindex="-1" class="ui-menu-item">
                <x-ui.icon name="user" :size="16" /> My Profile
            </a>
            <a href="{{ route('profile.security', absolute: false) }}" role="menuitem" tabindex="-1" class="ui-menu-item">
                <x-ui.icon name="shield" :size="16" /> Security
            </a>
            <a href="{{ route('profile.preferences', absolute: false) }}" role="menuitem" tabindex="-1" class="ui-menu-item">
                <x-ui.icon name="settings" :size="16" /> Preferences
            </a>
            <form role="none" method="post" action="{{ route('logout', absolute: false) }}" class="mt-1 border-t border-border pt-1">
                @csrf
                <button type="submit" role="menuitem" tabindex="-1" class="ui-menu-item">
                    <x-ui.icon name="log-out" :size="16" /> Sign out
                </button>
            </form>
        </x-ui.dropdown>
    </x-slot:account>
    @unless(request()->routeIs('dashboard'))
        <x-slot:breadcrumbs>
            <a class="underline underline-offset-4" href="{{ route('dashboard', absolute: false) }}">Workspace</a>
            <span aria-hidden="true">/</span> <span>{{ $title }}</span>
        </x-slot:breadcrumbs>
    @endunless
    <div class="space-y-4">
        @if(session('status'))<x-ui.alert variant="success" announce>{{ session('status') }}</x-ui.alert>@endif
        @if($errors->any())
            <x-ui.alert variant="error" title="Review the submitted changes" announce tabindex="-1" x-data x-init="$el.focus()">
                <x-ui.form-error :message="$errors->first()" />
            </x-ui.alert>
        @endif
        {{ $slot }}
    </div>
</x-layouts.app>
