<?php

namespace App\Commit;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\GeneratedQuestionsHistory;
use App\Models\Question;
use App\Validation\QuestionValidator;
use Illuminate\Support\Facades\DB;

class GeneratedQuestionCommitter
{
    /**
     * Commit all validated questions in a generated history session into the database.
     */
    public static function commitBatch(GeneratedQuestionsHistory $history): void
    {
        $questionsData = $history->validated_question ?? [];
        $context = $history->context ?? [];
        $courseId = $context['course_id'] ?? null;
        $examId = $context['exam_id'] ?? null;

        $exam = $examId ? Exam::find($examId) : null;

        DB::transaction(function () use ($questionsData, $history, $courseId, $exam) {
            foreach ($questionsData as $item) {
                // If item is wrapped in review envelope ['data' => [...]], unwrap it
                $data = $item['data'] ?? $item;

                $type = strtolower(trim((string) $data['type']));

                // 1. Create Question
                $question = Question::create([
                    'course_id'   => $courseId,
                    'created_by'  => $history->uploaded_by,
                    'type'        => $type,
                    'chapter'     => $data['chapter'] ?? null,
                    'content'     => $data['content'],
                    'difficulty'  => $data['difficulty'] ?? 'medium',
                    'status'      => 'active',
                ]);

                // 2. Create Options if MCQ or True/False
                $options = QuestionValidator::buildOptions(
                    $type,
                    $data['options'] ?? [],
                    $data['correct_answer'] ?? null
                );

                foreach ($options as $option) {
                    $question->options()->create($option);
                }

                // 3. Link to Exam if exam_id was provided
                if ($exam) {
                    self::linkToExam($question, $exam);
                }
            }

            $history->update([
                'status' => 'confirmed',
            ]);
        });
    }

    /**
     * Link question to exam with calculated marks.
     */
    private static function linkToExam(Question $question, Exam $exam): ExamQuestion
    {
        $typeKey = strtolower($question->type);
        $typeConfig = $exam->composition[$typeKey] ?? $exam->composition[$question->type] ?? null;

        $marks = (in_array($typeKey, ['mcq', 'true_false'], true) && isset($typeConfig['marks_each']))
            ? $typeConfig['marks_each']
            : 1;

        return ExamQuestion::create([
            'question_id' => $question->id,
            'exam_id'     => $exam->id,
            'marks'       => $marks,
        ]);
    }
}
