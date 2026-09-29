<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (empty($roles)) {
            return $next($request);
        }

        // Support comma separated strings in single parameter e.g. role:medsos_planner,medsos_editor
        $flatRoles = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $subRole) {
                $subRole = trim($subRole);
                if ($subRole !== '') {
                    $flatRoles[] = $subRole;
                }
            }
        }

        if ($user->hasRole($flatRoles)) {
            return $next($request);
        }

        abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang atau hak akses ke modul ini.');
    }
}
