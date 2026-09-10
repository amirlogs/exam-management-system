<?php

namespace App\Jobs;

use App\Models\GeneratedQuestionsHistory;
use App\Services\AiQuestionGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateQuestions implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(public GeneratedQuestionsHistory $generatedQuestionsHistory) {}

    public function handle(AiQuestionGenerator $generator): void
    {
        $this->generatedQuestionsHistory->update(['status' => 'processing']);
        try {
            $questions = $generator->generate(
                input: $this->generatedQuestionsHistory->input,
                count: (int) $this->generatedQuestionsHistory->count,
                isNote: (bool) $this->generatedQuestionsHistory->isNote,
                type: $this->generatedQuestionsHistory->type,
                difficulty: $this->generatedQuestionsHistory->difficulty,
                context: $this->generatedQuestionsHistory->context ?? []
            );

            $this->generatedQuestionsHistory->update([
                'validated_question' => $questions,
                'total_rows'         => count($questions),
                'valid_count'        => count($questions),
                'error_count'        => 0,
                'status'             => 'ready_for_review',
            ]);
        } catch (Throwable $e) {
            $this->generatedQuestionsHistory->update([
                'status'      => 'failed',
                'error_count' => (int) $this->generatedQuestionsHistory->count,
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->generatedQuestionsHistory->update([
            'status' => 'failed',
        ]);
    }
}
