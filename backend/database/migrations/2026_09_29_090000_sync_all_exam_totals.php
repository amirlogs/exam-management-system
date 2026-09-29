<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sync exams total_questions and total_marks
        $exams = DB::table('exams')->get();
        foreach ($exams as $exam) {
            $examQuestions = DB::table('exam_questions')->where('exam_id', $exam->id)->get();
            if ($examQuestions->isNotEmpty()) {
                DB::table('exams')->where('id', $exam->id)->update([
                    'total_questions' => $examQuestions->count(),
                    'total_marks' => (int) $examQuestions->sum('marks'),
                ]);
            }
        }

        // Sync practice_exams total_questions and total_marks
        $practiceExams = DB::table('practice_exams')->get();
        foreach ($practiceExams as $practiceExam) {
            $count = DB::table('practice_questions')->where('practice_exam_id', $practiceExam->id)->count();
            if ($count > 0) {
                DB::table('practice_exams')->where('id', $practiceExam->id)->update([
                    'total_questions' => $count,
                    'total_marks' => $count,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
