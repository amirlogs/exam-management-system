<?php

namespace App\Http\Middleware;

use App\Models\PracticeExam;
use App\Models\PracticeQuestion;
use App\Models\PracticeQuestionHistory;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePracticeOwnership
{
    /**
     * Handle an incoming request.
     *
     * Ensure only the user who created the practice exam, question, or history
     * can view, modify, or interact with it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // 1. Verify PracticeExam ownership if present in route parameters
        $practiceExam = $request->route('practiceExam');
        if ($practiceExam) {
            $ownerId = $practiceExam instanceof PracticeExam
                ? $practiceExam->owned_by
                : PracticeExam::where('id', $practiceExam)->value('owned_by');

            if ($ownerId !== null && (int) $ownerId !== (int) $user->id) {
                abort(403, 'Unauthorized. You do not own this practice exam.');
            }
        }

        // 2. Verify PracticeQuestionHistory ownership if present in route parameters
        $practiceQuestionHistory = $request->route('practiceQuestionHistory');
        if ($practiceQuestionHistory) {
            $ownerId = $practiceQuestionHistory instanceof PracticeQuestionHistory
                ? $practiceQuestionHistory->owned_by
                : PracticeQuestionHistory::where('id', $practiceQuestionHistory)->value('owned_by');

            if ($ownerId !== null && (int) $ownerId !== (int) $user->id) {
                abort(403, 'Unauthorized. You do not own this practice question history.');
            }
        }

        // 3. Verify PracticeQuestion ownership if present in route parameters
        $practiceQuestion = $request->route('practiceQuestion');
        if ($practiceQuestion) {
            $ownerId = $practiceQuestion instanceof PracticeQuestion
                ? $practiceQuestion->owned_by
                : PracticeQuestion::where('id', $practiceQuestion)->value('owned_by');

            if ($ownerId !== null && (int) $ownerId !== (int) $user->id) {
                abort(403, 'Unauthorized. You do not own this practice question.');
            }
        }

        return $next($request);
    }
}
