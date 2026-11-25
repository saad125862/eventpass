<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:organizer') or 'role:admin'. Admins always pass.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $allowed = $user->role === Role::Admin
            || in_array($user->role->value, $roles, true);

        abort_unless($allowed, 403, 'You do not have access to this area.');

        return $next($request);
    }
}
