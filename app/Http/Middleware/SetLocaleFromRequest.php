<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromRequest
{
    /**
     * Set the application locale from the request (header or query).
     * Priority: X-Locale header > Accept-Language (first supported) > lang query > config default.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['es', 'en']);
        $locale = $this->resolveLocale($request, $supported);

        if (in_array($locale, $supported, true)) {
            app()->setLocale($locale);
        } else {
            app()->setLocale(config('app.fallback_locale', 'en'));
        }

        return $next($request);
    }

    private function resolveLocale(Request $request, array $supported): ?string
    {
        // Explicit header (e.g. X-Locale: es)
        $header = $request->header('X-Locale');
        if ($header !== null && $header !== '') {
            return $this->normalizeLocale((string) $header);
        }

        // Query string (e.g. ?lang=es)
        $lang = $request->query('lang');
        if (is_string($lang) && $lang !== '') {
            return $this->normalizeLocale($lang);
        }

        // Accept-Language (e.g. "es-ES,es;q=0.9,en;q=0.8" -> try es, then en)
        $accept = $request->header('Accept-Language');
        if (is_string($accept) && $accept !== '') {
            return $this->parseAcceptLanguage($accept, $supported);
        }

        return null;
    }

    private function normalizeLocale(string $locale): string
    {
        return strtolower(explode('-', $locale)[0]);
    }

    private function parseAcceptLanguage(string $accept, array $supported): ?string
    {
        $parts = array_map('trim', explode(',', $accept));

        foreach ($parts as $part) {
            $lang = explode(';', $part)[0];
            $code = $this->normalizeLocale(trim($lang));
            if (in_array($code, $supported, true)) {
                return $code;
            }
        }

        return null;
    }
}
