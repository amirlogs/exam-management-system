<?php

namespace App\Http\Controllers;

use App\Jobs\GradeExamJob;
use App\Models\Exam;
use Illuminate\Http\Request;

class GradingController extends Controller
{
    public function autoGrade(Exam $exam, Request $request)
    {
        if ($exam->status !== 'completed') {
            return $this->success(null, 'Exam must be completed to be graded. ');
        }

        // dispatch job

        GradeExamJob::dispatch($exam, $request->user());

        return $this->success(null, 'Exam grading has been started. Results will be available when grading is completed.');
    }
}
