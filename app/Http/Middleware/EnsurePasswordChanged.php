<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
     public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            if (!$request->routeIs('password.change') && !$request->routeIs('password.update')) {
                return redirect()->route('password.change');
            }
        }

        return $next($request);
    }
}
