<x-layouts.app mode="public" class="flex items-center">
    @if (Route::has('login'))
        <x-slot:navigation>
            @auth
                <x-ui.button :href="url('/dashboard')" variant="ghost">Dashboard</x-ui.button>
            @else
                <x-ui.button :href="route('login')" variant="ghost">Log in</x-ui.button>
                @if(Route::has('register'))
                    <x-ui.button :href="route('register')">Register</x-ui.button>
                @endif
            @endauth
        </x-slot:navigation>
    @endif

    <section class="grid w-full items-center gap-10 py-10 sm:py-16 lg:grid-cols-[1fr_0.8fr] lg:gap-16">
        <div>
            <h1 class="ui-display-title max-w-2xl">Temporary email.<br class="hidden sm:block"> Keep your inbox yours.</h1>
            <p class="mt-5 max-w-md text-base leading-relaxed text-secondary-text sm:text-lg">Temp Mail is building disposable inboxes for messages you only need once.</p>
        </div>
        <x-ui.card class="min-w-0">
            <div class="flex items-center gap-3 border-b border-border pb-4">
                <span class="ui-icon-tile"><x-ui.icon name="mail" /></span>
                <h2 class="ui-card-title">Temporary inbox</h2>
                <x-ui.badge variant="outline" class="ms-auto">Coming soon</x-ui.badge>
            </div>
            <div class="mt-4">
                <x-ui.empty-state title="Inbox access" description="Temporary addresses will be available when the email service launches." />
            </div>
        </x-ui.card>
    </section>
    <x-slot:footer>
        <p class="ui-help">Temp Mail</p>
        @if(request()->attributes->get('active_languages')?->count() > 1)
            <nav aria-label="Languages" class="flex flex-wrap gap-3">
                @foreach(request()->attributes->get('active_languages') as $language)
                    <a class="underline underline-offset-4" href="{{ route('locale.home', $language->code, absolute: false) }}"
                        lang="{{ $language->code }}" dir="{{ $language->direction }}"
                        @if(app()->getLocale() === $language->code) aria-current="page" @endif>{{ $language->native_name }}</a>
                @endforeach
            </nav>
        @endif
    </x-slot:footer>
</x-layouts.app>
