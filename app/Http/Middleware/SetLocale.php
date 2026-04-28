<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the application locale from a `locale` route parameter and persists
 * the choice in the session so subsequent requests without a prefix keep
 * the same language.
 */
final class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var array<int, string> $supported */
        $supported = (array) config('public_pages.locales', ['en']);

        $locale = (string) $request->route('locale', '');

        if ($locale === '' || ! in_array($locale, $supported, true)) {
            $locale = $this->resolveFallbackLocale($request, $supported);
        }

        app()->setLocale($locale);
        $request->session()->put('locale', $locale);

        URL::defaults(['locale' => $locale]);

        return $next($request);
    }

    /**
     * Pick a sensible locale when the route has no explicit prefix: the
     * session value first, otherwise the configured default.
     *
     * @param  array<int, string>  $supported
     */
    private function resolveFallbackLocale(Request $request, array $supported): string
    {
        $sessionLocale = (string) $request->session()->get('locale', '');

        if ($sessionLocale !== '' && in_array($sessionLocale, $supported, true)) {
            return $sessionLocale;
        }

        return (string) config('public_pages.default_locale', 'en');
    }
}
