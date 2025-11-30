<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePaid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Con Cashier basta con subscribed('default')
        if (! $user || ! $user->subscribed('default')) {
            return redirect()->route('billing')->with('status', 'Necesitas una suscripción activa.');
        }

        return $next($request);
    }
}
