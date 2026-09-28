<?php

namespace App\Services;

use App\Models\PracticeAnswers;
use App\Models\PracticeQuestion;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use RuntimeException;

class QuestionGuidanceGenerator
{
    public function generate(
        string $prompt,
        PracticeQuestion $question,
        $history,
        ?PracticeAnswers $studentAnswer = null
    ): string {
        $systemPrompt = "You are an expert, supportive AI educational tutor helping a student learn and practice.\n"
            ."You are provided with the full question stem, all available options labeled as Option A, Option B, Option C, Option D, etc. (including which is the official correct answer), and the student's submitted attempt.\n\n"
            ."Instructions:\n"
            ."1. If the student asks why a specific option (e.g. 'why not D?', 'why is B wrong?', 'why is A the answer?') is or is not the answer, directly analyze that option using the choices provided above. Explain why that specific option is incorrect or correct, and identify the underlying misunderstanding.\n"
            ."2. Explain key concepts, definitions, and logic clearly, step by step, so the student genuinely understands.\n"
            ."3. Be encouraging, concise, educational, and constructive.\n"
            ."4. Use Markdown formatting (bolding, bullet points) to keep explanations easily readable.\n"
            .'5. Do not invent options or contradict the given question context.';

        // Format options / choices
        $options = $question->options ? $question->options->sortBy('id')->values() : collect();
        $optionsLines = [];
        $chosenOptionText = null;

        foreach ($options as $index => $option) {
            $letter = chr(65 + $index);
            $isCorrect = (bool) $option->is_correct;
            $suffix = $isCorrect ? ' [Official Correct Answer]' : '';
            $optionsLines[] = "Option {$letter}: {$option->option_text}{$suffix}";

            if ($studentAnswer && $studentAnswer->selected_option_id === $option->id) {
                $chosenOptionText = "Option {$letter} (\"{$option->option_text}\")";
            }
        }

        $optionsBlock = ! empty($optionsLines) ? implode("\n", $optionsLines) : 'No multiple choice options (Written/Essay question).';

        // Format student attempt
        $attemptBlock = 'None (Not answered yet)';
        if ($studentAnswer) {
            if ($chosenOptionText) {
                $statusStr = $studentAnswer->is_correct ? 'Correct' : 'Incorrect';
                $attemptBlock = "Selected: {$chosenOptionText} ({$statusStr})";
            } elseif (! empty($studentAnswer->answer_text)) {
                $attemptBlock = "Written answer: \"{$studentAnswer->answer_text}\"";
            }
        }

        $historyText = $history
            ->map(function ($item) {
                return "Student: {$item->prompt}\n"
                    ."Tutor: {$item->response}";
            })
            ->implode("\n\n---\n\n");

        $questionText = "Question Stem:\n{$question->content}\n\n"
            ."Question Type: {$question->type}\n"
            ."Difficulty: {$question->difficulty}\n\n"
            ."Choices / Options:\n{$optionsBlock}\n\n"
            ."Student's Submitted Attempt:\n{$attemptBlock}\n\n"
            ."Student's Inquiry:\n{$prompt}\n\n"
            ."Previous Q&A Turns:\n"
            .($historyText ?: 'None');

        $response = Prism::text()
            ->using(Provider::Gemini, 'gemini-3.5-flash-lite')
            ->withClientRetry(3, 1000)
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($questionText)
            ->asText();

        $guidance = trim($response->text);

        if ($guidance === '') {
            throw new RuntimeException('The AI model failed to generate question guidance.');
        }

        return $guidance;
    }
}
