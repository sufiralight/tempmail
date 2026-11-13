@props(['title' => null, 'mode' => 'dashboard', 'heading' => null, 'description' => null, 'logoSrc' => null])

@php($hasAssets = app(Illuminate\Foundation\Vite::class)->isRunningHot() || is_file(public_path('build/manifest.json')))
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ request()->attributes->get('language')?->direction ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @if(isset($workspaceBranding['favicon']))
        <link rel="icon" href="{{ route('branding.asset', ['slot' => 'favicon']) }}">
    @endif
    @if(isset($workspaceBranding['social']))
        <meta property="og:image" content="{{ route('branding.asset', ['slot' => 'social']) }}">
    @endif
    <x-layouts.theme-init />
    @if($typography = request()->attributes->get('typography'))
        <x-layouts.typography :fonts="$typography['fonts']" :stylesheet="$typography['stylesheet']" />
    @endif
    @if ($hasAssets)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>[x-cloak]{display:none!important}body{font:1rem system-ui;margin:1rem;color:#18181b;background:#fafafa}a{color:inherit}</style>
    @endif
</head>
<body class="flex min-h-screen flex-col">
    <x-ui.page-progress />
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:bg-surface focus:p-2">Skip to content</a>
    <div @class(['flex flex-1 flex-col', 'lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]' => isset($sidebar)])>
        @isset($sidebar)
            <aside class="hidden border-e border-border bg-surface lg:block">
                <div class="sticky top-0 max-h-dvh overflow-y-auto px-3 pb-5">
                    <div class="flex min-h-16 items-center px-2">
                        <x-ui.logo :href="route('dashboard', absolute: false)" :src="isset($workspaceBranding['logo']) ? route('branding.asset', ['slot' => 'logo']) : null"
                            :dark-src="isset($workspaceBranding['dark-logo']) ? route('branding.asset', ['slot' => 'dark-logo']) : null" />
                    </div>
                    {{ $sidebar }}
                </div>
            </aside>
        @endisset
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="border-b border-border bg-surface">
                <div @class(['ui-container flex items-center justify-between gap-2 py-2', 'min-h-16 flex-wrap' => !isset($sidebar), 'min-h-14 max-w-none!' => isset($sidebar)])>
                    @if(isset($sidebar))
                        {{ $context ?? '' }}
                    @else
                        <x-ui.logo :href="url('/')" :src="$logoSrc ?? (isset($workspaceBranding['logo']) ? route('branding.asset', ['slot' => 'logo']) : null)"
                            :dark-src="$logoSrc === null && isset($workspaceBranding['dark-logo']) ? route('branding.asset', ['slot' => 'dark-logo']) : null" />
                    @endif
                    <div class="flex shrink-0 items-center gap-2">
                        @isset($navigation)
                            <nav aria-label="Main navigation" class="flex flex-wrap items-center gap-2">{{ $navigation }}</nav>
                        @endisset
                        @if($hasAssets)<x-ui.theme-switcher x-cloak />@endif
                        @isset($account){{ $account }}@endisset
                    </div>
                </div>
            </header>

            <main id="main-content" tabindex="-1" {{ $attributes->class(['ui-container flex-1', 'py-8 sm:py-12' => !isset($sidebar), 'py-6 sm:py-8' => isset($sidebar), 'max-w-xl!' => $mode === 'auth', 'max-w-3xl!' => $mode === 'installer']) }}>
                @isset($breadcrumbs)
                    <nav aria-label="Breadcrumb" class="ui-help mb-5">{{ $breadcrumbs }}</nav>
                @endisset
                @if ($heading)
                    <div class="mb-7">
                        <h1 class="ui-page-title">{{ $heading }}</h1>
                        @if($description)<p class="ui-help mt-2">{{ $description }}</p>@endif
                    </div>
                @endif
                {{ $slot }}
            </main>

            @isset($footer)
                <footer class="border-t border-border">
                    <div class="ui-container py-5">{{ $footer }}</div>
                </footer>
            @endisset
        </div>
    </div>
</body>
</html>
