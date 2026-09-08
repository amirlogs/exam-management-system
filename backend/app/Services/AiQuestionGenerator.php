<?php

namespace App\Services;

use App\Models\Question;
use App\Validation\QuestionValidator;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use RuntimeException;

class AiQuestionGenerator
{
    public function generate(string $input, int $count = 3, bool $isNote = false, string $type = 'mcq', string $difficulty = 'medium', array $context = []): array
    {
        $courseId = $context['course_id'] ?? null;
        if ($courseId && ! isset($context['existing_contents'])) {
            $context['existing_contents'] = Question::where('course_id', $courseId)
                ->pluck('content')
                ->map(fn($text) => strtolower(trim((string) $text)))
                ->toArray();
        }

        $prompt = $isNote
            ? "Context Notes:\n{$input}\n\nTask: Generate exactly {$count} {$difficulty} {$type} questions based strictly on these notes."
            : "Generate exactly {$count} {$difficulty} {$type} questions about: {$input}";

        $systemPrompt = "You are an educational assessment assistant. Generate a list of questions following these rules strictly for every question:\n"
            . "1. If type is 'mcq', 'options' must contain at least 2 distinct strings, and 'correct_answer' must match one of the options EXACTLY.\n"
            . "2. If type is 'true_false', 'options' must be ['True', 'False'], and 'correct_answer' must be either 'True' or 'False'.\n"
            . "3. If type is 'essay' or 'short_answer', 'options' must be null.\n"
            . "4. 'content' must be at least 5 characters.\n"
            . "5. Return exactly the requested number of questions.";

        if ($isNote) {
            $systemPrompt .= "\n6. Base all questions ONLY on the provided context notes.";
        }


        //call the ai model
        $response = Prism::structured()
            ->using(Provider::Gemini, 'gemini-2.5-flash-lite')
            ->withClientRetry(3, 1000)
            ->withSchema($this->questionsListSchema())
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($prompt)
            ->asStructured();

        $questions = $response->structured['questions'] ?? [];

        if (empty($questions)) {
            throw new RuntimeException('The AI model failed to generate any questions.');
        }

        $seen = [];
        foreach ($questions as $index => $questionData) {
            $errors = QuestionValidator::validate($questionData, $context);

            if (! empty($errors)) {
                throw new RuntimeException("Question #" . ($index + 1) . " failed validation: " . implode(', ', $errors));
            }

            // Intra-batch duplicate check
            $normalizedContent = strtolower(trim((string) ($questionData['content'] ?? '')));
            if (isset($seen[$normalizedContent])) {
                throw new RuntimeException("Question #" . ($index + 1) . " is a duplicate of Question #" . ($seen[$normalizedContent] + 1));
            }
            $seen[$normalizedContent] = $index;
        }

        return $questions;
    }

    public function questionsListSchema(): ObjectSchema
    {
        $singleQuestion = new ObjectSchema(
            name: 'question_item',
            description: 'A single question entry',
            properties: [
                new EnumSchema(
                    name: 'type',
                    description: 'The question format',
                    options: ['mcq', 'essay', 'true_false', 'short_answer']
                ),
                new StringSchema(
                    name: 'content',
                    description: 'The question body or text (minimum 5 characters)'
                ),
                new StringSchema(
                    name: 'chapter',
                    description: 'Optional chapter title or topic name (max 255 characters)',
                    nullable: true
                ),
                new ArraySchema(
                    name: 'options',
                    description: 'Answer choices. Required for mcq and true_false. Null for essay and short_answer.',
                    items: new StringSchema('option', 'An answer option string'),
                    nullable: true
                ),
                new StringSchema(
                    name: 'correct_answer',
                    description: 'The correct answer matching one item in options.',
                    nullable: true
                ),
                new EnumSchema(
                    name: 'difficulty',
                    description: 'Difficulty level',
                    options: ['easy', 'medium', 'hard']
                ),
            ],
            requiredFields: ['type', 'content', 'chapter', 'options', 'correct_answer', 'difficulty']
        );

        return new ObjectSchema(
            name: 'questions_payload',
            description: 'A collection of generated questions',
            properties: [
                new ArraySchema(
                    name: 'questions',
                    description: 'The list of generated questions',
                    items: $singleQuestion
                ),
            ],
            requiredFields: ['questions']
        );
    }
}
