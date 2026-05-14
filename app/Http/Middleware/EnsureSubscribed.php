<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSubscribed
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && !$user->subscribed('default') && !$user->is_dev && $user->plan_type !== 'beta') {
            return redirect()->route('billing');
        }

        return $next($request);
    }
}
