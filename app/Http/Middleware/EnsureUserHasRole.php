<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route to users holding any of the given clinic roles.
 * Usage: ->middleware('role:staff,admin')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $roles = array_map('trim', $roles);

        if ($roles !== [] && ! $request->user()?->hasRole(...$roles)) {
            if (! $request->user()) {
                return redirect()->route('login');
            }

            abort(403, sprintf('This area requires role: %s.', implode(' or ', $roles)));
        }

        return $next($request);
    }
}
