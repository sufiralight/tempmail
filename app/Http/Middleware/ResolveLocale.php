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
        $preferred = $request->session()->get('locale');
        $language = is_string($explicit)
            ? $active->firstWhere('code', $this->registry->normalize($explicit))
            : (is_string($preferred) ? $active->firstWhere('code', $this->registry->normalize($preferred)) : null) ?? $default;
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
            return $next($request);
        } finally {
            $typography = app(Typography::class);
            if ($language !== null && $typography->ready()) {
                $fonts = $typography->resolve($language->id, $language->code);
                $request->attributes->set('typography', ['fonts' => $fonts, 'stylesheet' => $typography->stylesheet($fonts)]);
            }
            if ($language !== null) {
                $request->attributes->set('ui_translations', app(UiTranslations::class)->published($language->id));
            }
            app()->setLocale($previous);
        }
    }
}
