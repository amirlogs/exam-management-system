<?php

namespace App\Commit;

use App\Models\PracticeQuestion;
use App\Models\PracticeQuestionHistory;
use App\Validation\QuestionValidator;

class PracticeQuestionCommitter
{
    public static function commit(array $data, PracticeQuestionHistory $practiceQuestionHistory): void
    {
        $type = strtolower(trim((string) $data['type']));
        $question = PracticeQuestion::create([
            'owned_by' => $practiceQuestionHistory->owned_by,
            'practice_exam_id' => $practiceQuestionHistory->practice_exam_id,
            'practice_question_histories_id' => $practiceQuestionHistory->id,
            'type' => $type,
            'content' => $data['content'],
            'difficulty' => $data['difficulty'],
            'status' => 'active',
        ]);

        foreach (QuestionValidator::buildOptions($type, $data['options'] ?? [], $data['correct_answer'] ?? null) as $option) {
            $question->options()->create($option);
        }
    }
}
