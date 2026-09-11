<?php

namespace App\Jobs;

use App\Models\PracticeQuestionHistory;
use App\Services\AiQuestionGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GeneratePracticeQuestions implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(public PracticeQuestionHistory $practiceQuestionHistory) {}

    public function handle(AiQuestionGenerator $generator): void
    {
        $this->practiceQuestionHistory->update(['status' => 'processing']);
        try {
            $questions = $generator->generate(
                input: $this->practiceQuestionHistory->input,
                count: (int) $this->practiceQuestionHistory->count,
                isNote: (bool) $this->practiceQuestionHistory->isNote,
                type: $this->practiceQuestionHistory->type,
                difficulty: $this->practiceQuestionHistory->difficulty,
                context: $this->practiceQuestionHistory->context ?? []
            );

            $this->practiceQuestionHistory->update([
                'validated_question' => $questions,
                'total_rows' => count($questions),
                'valid_count' => count($questions),
                'error_count' => 0,
                'status' => 'ready_for_review',
            ]);
        } catch (Throwable $e) {
            $this->practiceQuestionHistory->update([
                'status'      => 'failed',
                'error_count' => (int) $this->practiceQuestionHistory->count,
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->practiceQuestionHistory->update([
            'status' => 'failed',
        ]);
    }
}
