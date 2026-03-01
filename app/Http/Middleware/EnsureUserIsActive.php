<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if ($user && !$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Akun Anda nonaktif. Hubungi administrator.');
        }

        return $next($request);
    }
}

