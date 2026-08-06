<?php

namespace App\Commit;

use App\Models\Question;

class QuestionCommitter
{
    public static function commit(array $data, array $context = []): void
    {
        $question = Question::create([
            'course_id' => $context['course_id'],
            'created_by' => $context['uploaded_by'],
            'type' => $data['type'],
            'chapter' => $data['chapter'] ?? null,
            'content' => $data['content'],
            'difficulty' => $data['difficulty'],
            'status' => 'draft',
        ]);

        if (in_array($data['type'], ['MCQ', 'TRUE_FALSE']) && ! empty($data['options'])) {
            $options = is_array($data['options']) ? $data['options'] : json_decode($data['options'], true);
            $correctAnswer = strtolower(trim((string) ($data['correct_answer'] ?? '')));

            foreach ($options as $optionText) {
                $question->options()->create([
                    'option_text' => $optionText,
                    'is_correct' => strtolower(trim((string) $optionText)) === $correctAnswer,
                ]);
            }
        }
    }
}
