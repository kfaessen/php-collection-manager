<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetupMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip setup check for setup routes
        if ($request->is('setup*')) {
            return $next($request);
        }

        // Check if application is set up
        if (!$this->isApplicationSetup()) {
            return redirect()->route('setup.welcome');
        }

        return $next($request);
    }

    /**
     * Check if application is already set up.
     */
    private function isApplicationSetup()
    {
        try {
            // Check if migrations table exists and has been run
            if (!Schema::hasTable('migrations')) {
                return false;
            }

            $migrations = DB::table('migrations')->count();
            return $migrations > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
} 