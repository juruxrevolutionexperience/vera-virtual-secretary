<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user        = $request->user();
        $superAdmins = config('app.super_admins', []);

        if (!$user || empty($superAdmins) || !in_array($user->email, $superAdmins, true)) {
            abort(403, 'Acceso restringido.');
        }

        return $next($request);
    }
}
