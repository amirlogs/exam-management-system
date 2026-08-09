<?php

namespace App\Commit;

use App\Models\Question;
use App\Validation\QuestionValidator;

class QuestionCommitter
{
    public static function commit(array $data, $importHistory): void
    {
        $context = $importHistory['context'] ?? [];
        $question = Question::create([
            'course_id' => $context['course_id'],
            'created_by' => $context['uploaded_by'],
            'import_history_id' => $importHistory->id,
            'type' => $data['type'],
            'chapter' => $data['chapter'] ?? null,
            'content' => $data['content'],
            'difficulty' => $data['difficulty'],
            'status' => 'active',
        ]);

        foreach (QuestionValidator::buildOptions($data['type'], $data['options'] ?? [], $data['correct_answer'] ?? null) as $option) {
            $question->options()->create($option);
        }
    }
}
