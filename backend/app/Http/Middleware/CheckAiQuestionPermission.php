<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAiQuestionPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->hasPermission('question.create') && ! $user->hasPermission('question.create_all')) {
            abort(403, 'Missing permission: question.create');
        }

        return $next($request);
    }
}
