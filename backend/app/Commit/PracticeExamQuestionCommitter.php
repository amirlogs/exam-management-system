<?php

namespace App\Commit;

use App\Models\ExamQuestion;

class PracticeExamQuestionCommitter
{
    public static function commit($question, $exam): void
    {

        $typeConfig = $exam->composition[$question['type']] ?? null;

        if (in_array($question->type, ['MCQ', 'TRUE_FALSE'])) {
            $marks = $typeConfig['marks_each'];
        } else {
            $marks = 1;
        }

        $examQuestion =  ExamQuestion::create([
            'question_id' => $question->id,
            'exam_id' => $exam->id,
            'marks' => $marks,
        ]);
    }
}
