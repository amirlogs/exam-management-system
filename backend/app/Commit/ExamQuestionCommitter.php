<?php

namespace App\Commit;

use App\Models\ExamQuestion;

class ExamQuestionCommitter
{
    public static function commit($question, $exam):void
    {
        
        $typeConfig = $exam->composition[$question['type']] ?? null;

        if (in_array($question->type, ['MCQ', 'TRUE_FALSE'])) {
            $marks = $typeConfig['marks_each'];
        } else {
            $marks = 1;
        }

        ExamQuestion::create([
            'question_id' => $question->id,
            'exam_id' => $exam->id,
            'marks' => $marks,
        ]);
    }
}
