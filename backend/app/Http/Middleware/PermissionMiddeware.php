<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddeware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::guard('sanctum')->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
                'data' => null,
                'errors' => null,
            ]);
        }

        if (! $user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have the required permission',
                'data' => null,
                'errors' => null,
            ]);
        }

        return $next($request);
    }
}
