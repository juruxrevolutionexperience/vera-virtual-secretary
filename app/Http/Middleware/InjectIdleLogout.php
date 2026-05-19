<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InjectIdleLogout
{
    /**
     * Inyecta el partial components/idle-logout.blade.php antes de </body>
     * en cualquier respuesta HTML de un usuario autenticado.
     *
     * Así no hace falta tocar las +15 vistas standalone (vera/onboarding,
     * vera/dashboard, vera/chat, billing/*, settings/*, etc.) ni acordarse
     * de incluirlo en cada vista nueva.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!Auth::check()) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || stripos($content, '</body>') === false) {
            return $response;
        }

        try {
            $partial = View::make('components.idle-logout')->render();
            $response->setContent(preg_replace('/<\/body>/i', $partial . '</body>', $content, 1));
        } catch (Throwable $e) {
            // Si el partial falla por cualquier motivo, NO rompemos la página:
            // devolvemos el HTML original sin el modal. Logueamos para diagnóstico.
            Log::warning('InjectIdleLogout: render del partial falló', [
                'error' => $e->getMessage(),
                'url'   => $request->fullUrl(),
            ]);
        }

        return $response;
    }
}
