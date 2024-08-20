<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('web')->check()) { // Ensure correct guard is used
            return redirect()->route('filament.admin.pages.dashboard'); // Redirect to your dashboard route
        }

        return $next($request);
    }
}
