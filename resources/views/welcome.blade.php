@php($ui = app(\App\Administration\UiTranslations::class))
<x-layouts.app mode="public" class="flex items-center">
    @if (Route::has('login'))
        <x-slot:navigation>
            @auth
                <x-ui.button :href="url('/dashboard')" variant="ghost">{{ $ui->text('navigation.dashboard') }}</x-ui.button>
            @else
                <x-ui.button :href="route('login')" variant="ghost">{{ $ui->text('auth.login') }}</x-ui.button>
                @if(Route::has('register'))
                    <x-ui.button :href="route('register')">{{ $ui->text('auth.register') }}</x-ui.button>
                @endif
            @endauth
        </x-slot:navigation>
    @endif

    <section class="grid w-full items-center gap-10 py-10 sm:py-16 lg:grid-cols-[1fr_0.8fr] lg:gap-16">
        <div>
            <h1 class="ui-display-title font-preview-heading max-w-2xl">{{ $ui->text('home.headline') }}</h1>
            <p class="mt-5 max-w-md text-base leading-relaxed text-secondary-text sm:text-lg">{{ $ui->text('home.description') }}</p>
        </div>
        <x-ui.card class="min-w-0">
            <div class="flex items-center gap-3 border-b border-border pb-4">
                <span class="ui-icon-tile"><x-ui.icon name="mail" /></span>
                <h2 class="ui-card-title">{{ $ui->text('home.inbox') }}</h2>
                <x-ui.badge variant="outline" class="ms-auto">{{ $ui->text('home.coming_soon') }}</x-ui.badge>
            </div>
            <div class="mt-4">
                <x-ui.empty-state :title="$ui->text('home.inbox_access')" :description="$ui->text('home.availability')" />
            </div>
        </x-ui.card>
    </section>
    <x-slot:footer>
        <p class="ui-help">Temp Mail</p>
        @if(request()->attributes->get('active_languages')?->count() > 1)
            <nav aria-label="{{ $ui->text('navigation.languages') }}" class="flex flex-wrap gap-3">
                @foreach(request()->attributes->get('active_languages') as $language)
                    <form method="post" action="{{ route('locale.preference', $language->code, absolute: false) }}">
                        @csrf
                        <x-ui.button type="submit" size="sm" variant="ghost" :lang="$language->code" :dir="$language->direction"
                            :aria-current="app()->getLocale() === $language->code ? 'page' : null">{{ $language->native_name }}</x-ui.button>
                    </form>
                @endforeach
            </nav>
        @endif
    </x-slot:footer>
</x-layouts.app>
