@props(['title' => null, 'mode' => 'dashboard', 'heading' => null, 'description' => null, 'logoSrc' => null])

@php($hasAssets = app(Illuminate\Foundation\Vite::class)->isRunningHot() || is_file(public_path('build/manifest.json')))
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <x-layouts.theme-init />
    @if ($hasAssets)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>[x-cloak]{display:none!important}body{font:1rem system-ui;margin:1rem;color:#18181b;background:#fafafa}a{color:inherit}</style>
    @endif
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:bg-surface focus:p-2">Skip to content</a>
    <header class="border-b border-border bg-surface">
        <div class="ui-container flex min-h-16 flex-wrap items-center justify-between gap-3 py-2">
            <x-ui.logo :href="url('/')" :src="$logoSrc" />
            <div class="flex flex-wrap items-center gap-2">
                @isset($navigation)
                    <nav aria-label="Main navigation" class="flex flex-wrap items-center gap-2">{{ $navigation }}</nav>
                @endisset
                @if($hasAssets)<x-ui.theme-switcher x-cloak />@endif
            </div>
        </div>
    </header>

    <main id="main-content" tabindex="-1" {{ $attributes->class(['ui-container flex-1 py-8 sm:py-12', 'max-w-xl!' => $mode === 'auth', 'max-w-3xl!' => $mode === 'installer']) }}>
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
</body>
</html>
