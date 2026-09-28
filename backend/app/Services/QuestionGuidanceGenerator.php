<?php

namespace App\Services;

use App\Models\PracticeQuestion;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use RuntimeException;

class QuestionGuidanceGenerator
{
    public function generate(
        string $prompt,
        PracticeQuestion $question,
        $history
    ): string {
        $systemPrompt = "You are an educational assessment assistant. "
            . "Guide the student toward understanding and solving the given question.\n"
            . "1. Do not simply give the final answer unless explicitly requested.\n"
            . "2. Explain the concept clearly and provide useful hints and reasoning steps.\n"
            . "3. Adapt the guidance to the student's request.\n"
            . "4. Do not invent information that is not present in the question.\n"
            . "5. Use previous interactions when they are relevant to the student's request.\n"
            . "6. Keep the guidance educational, clear, and concise.";

        $historyText = $history
            ->map(function ($item) {
                return "Question: {$item->practiceQuestion->content}\n"
                    . "Student: {$item->prompt}\n"
                    . "Assistant: {$item->response}";
            })
            ->implode("\n\n--- Previous Interaction ---\n\n");

        $questionText = "Current question:\n{$question->content}\n\n"
            . "Type: {$question->type}\n"
            . "Difficulty: {$question->difficulty}\n\n"
            . "Student request:\n{$prompt}\n\n"
            . "Previous interactions:\n"
            . ($historyText ?: 'None');

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
