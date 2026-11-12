<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A form submitted in the background follows its redirect inside the same request chain. When that
 * page uses another layout the browser loads it again, so the flash message must survive one more request.
 */
class KeepFlashAcrossLayouts
{
    public const string Header = 'X-Emca-Layout';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $currentLayout = $request->header(self::Header);

        if (! is_string($currentLayout) || ! $request->isMethod('GET') || ! $request->hasSession()) {
            return $response;
        }

        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content)) {
            return $response;
        }

        preg_match('/<body[^>]*\sdata-emca-layout="([^"]*)"/i', $content, $match);

        if (($match[1] ?? null) !== $currentLayout) {
            $request->session()->reflash();
        }

        return $response;
    }
}
