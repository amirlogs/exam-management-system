<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $permissions = explode('|', $permission);
        $hasAny = false;
        foreach ($permissions as $p) {
            if ($user->hasPermission(trim($p))) {
                $hasAny = true;
                break;
            }
        }

        if (! $hasAny) {
            abort(403, "Missing permission: {$permission}");
        }

        return $next($request);
    }
}
