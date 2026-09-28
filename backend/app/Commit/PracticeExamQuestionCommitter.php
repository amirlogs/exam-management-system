<?php

namespace App\Commit;

use App\Models\PracticeExamQuestion;

class PracticeExamQuestionCommitter
{
    public static function commit($question, $examId): void
    {
        PracticeExamQuestion::create([
            'practice_exam_id' => $examId,
            'practice_question_id' => $question->id,
        ]);
    }
}
