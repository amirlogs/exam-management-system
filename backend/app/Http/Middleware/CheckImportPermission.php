<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckImportPermission
{
    private array $permissions = [
        'students' => 'student.import',
        'instructors' => 'instructor.import',
        'sections' => 'section.import',
        'questions' => 'question.import',
        'users' => 'user.import',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $type = $request->route('type');

        if (! $type) {
            $importHistory = $request->route('importHistory');

            if ($importHistory) {
                $type = $importHistory->type;
            }
        }

        if (! isset($this->permissions[$type])) {
            abort(422, 'Invalid import type.');
        }

        $permission = $this->permissions[$type];

        if (! $user->hasPermission($permission)) {
            abort(403, "Missing permission: {$permission}");
        }

        return $next($request);
    }
}
