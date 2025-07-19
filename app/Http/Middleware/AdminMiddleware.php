<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user is admin or has admin permissions
        if (!$user->isAdmin() && !$user->hasPermissionTo('admin.access')) {
            abort(403, 'Toegang geweigerd. Administrator rechten vereist.');
        }

        return $next($request);
    }
}
