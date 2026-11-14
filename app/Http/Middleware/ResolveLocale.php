<?php

namespace App\Http\Middleware;

use App\Administration\LanguageRegistry;
use App\Administration\Typography;
use App\Administration\UiTranslations;
use App\Installation\InstallationAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveLocale
{
    public function __construct(private LanguageRegistry $registry, private InstallationAccess $installation) {}

    public function handle(Request $request, Closure $next): Response
    {
        $previous = app()->getLocale();
        $explicit = $request->route('locale');
        if (! $this->installation->installed() || ! $this->registry->ready()) {
            abort_if(is_string($explicit), 404);

            return $next($request);
        }

        $active = $this->registry->active()->addSelect('languages.*')->selectSub($this->registry->defaults()->select('language_id'), 'default_language_id')->get();
        $default = $active->firstWhere('id', $active->first()?->default_language_id);
        $preferred = $request->session()->get('locale', $request->user()?->interface_locale);
        $language = is_string($explicit)
            ? $active->firstWhere('code', $this->registry->normalize($explicit))
            : (is_string($preferred) ? $active->firstWhere('code', $this->registry->normalize($preferred)) : null) ?? $active->firstWhere('code', $this->browserLocale($request, $active->pluck('code')->all())) ?? $default ?? $active->first();
        abort_if(is_string($explicit) && $language === null, 404);

        $request->attributes->set('language', $language);
        $request->attributes->set('active_languages', $active);
        $typography = app(Typography::class);
        if ($language !== null && $typography->ready()) {
            $fonts = $typography->resolve($language->id, $language->code);
            $request->attributes->set('typography', ['fonts' => $fonts, 'stylesheet' => $typography->stylesheet($fonts)]);
        }
        if ($language !== null) {
            $request->attributes->set('ui_translations', app(UiTranslations::class)->published($language->id));
        }
        app()->setLocale($language->code ?? config('app.locale'));

        try {
            return $next($request)->setPrivate()->setVary(['Accept-Language', 'Cookie'], false);
        } finally {
            app()->setLocale($previous);
        }
    }

    public function select(Request $request, string $locale): Response
    {
        abort_unless($this->installation->installed() && $this->registry->ready(), 404);
        $code = $this->registry->normalize($locale);
        abort_unless($this->registry->active()->where('code', $code)->exists(), 404);
        $request->session()->put('locale', $code);

        return redirect()->route('locale.home', $code);
    }

    /** @param list<string> $active */
    private function browserLocale(Request $request, array $active): ?string
    {
        $ranked = [];
        $excluded = [];
        foreach (array_slice(explode(',', substr((string) $request->header('Accept-Language'), 0, 4096)), 0, 40) as $index => $part) {
            if (! preg_match('/^([a-z]{2,3}(?:[-_][a-z0-9]{2,8})*)(?:\s*;\s*q=(0(?:\.[0-9]{1,3})?|1(?:\.0{1,3})?))?$/iD', trim($part), $match)) {
                continue;
            }
            $code = $this->registry->normalize($match[1]);
            $quality = (float) ($match[2] ?? 1);
            if ($quality === 0.0) {
                $excluded[$code] = true;

                continue;
            }
            $ranked[] = [$code, $quality, $index];
        }
        usort($ranked, fn ($a, $b) => ($b[1] <=> $a[1]) ?: ($a[2] <=> $b[2]));
        foreach ($ranked as [$code]) {
            foreach (array_unique([$code, explode('-', $code, 2)[0]]) as $candidate) {
                if (! isset($excluded[$candidate]) && in_array($candidate, $active, true)) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
