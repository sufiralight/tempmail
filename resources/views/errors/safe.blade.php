<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Temp Mail</title>
    <x-layouts.theme-init :use-account="false" />
    <style>
        :root{color-scheme:light;--page:#fafafa;--surface:#fff;--text:#18181b;--border:#e4e4e7;--muted:#52525b}
        @media(prefers-color-scheme:dark){:root:not([data-theme]){color-scheme:dark;--page:#18181b;--surface:#27272a;--text:#fafafa;--border:#3f3f46;--muted:#a1a1aa}}
        :root[data-theme=dark]{color-scheme:dark;--page:#18181b;--surface:#27272a;--text:#fafafa;--border:#3f3f46;--muted:#a1a1aa}
        body{margin:0;padding:1.25rem;min-height:100dvh;box-sizing:border-box;display:grid;place-items:center;background:var(--page);color:var(--text);font:1rem/1.5 var(--font-sans,system-ui,sans-serif)}
        main{max-width:30rem;width:100%;box-sizing:border-box;padding:clamp(1.25rem,5vw,2rem);border:1px solid var(--border);border-radius:.75rem;background:var(--surface)}
        h1{font-size:1.75rem;line-height:1.2;margin:.5rem 0} p{color:var(--muted)} a{display:inline-flex;align-items:center;min-height:44px;padding:0 1rem;border-radius:.5rem;background:var(--text);color:var(--page);text-decoration:none}a:focus-visible{outline:2px solid var(--text);outline-offset:3px}
    </style>
</head>
<body>
    <main>
        <p>{{ $status }}</p>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        @if($installer ?? false)<a href="/install">Return to setup</a>
        @elseif($status < 500)<a href="/">Return home</a>@endif
    </main>
</body>
</html>
