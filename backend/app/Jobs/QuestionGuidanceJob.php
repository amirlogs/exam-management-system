<?php

namespace App\Jobs;

use App\Models\QuestionGuidances;
use App\Services\QuestionGuidanceGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class QuestionGuidanceJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public QuestionGuidances $questionGuidances) {}

    /**
     * Execute the job.
     */
    public function handle(QuestionGuidanceGenerator $generator): void
    {
        try {
            $question = $this->questionGuidances->practiceQuestion;
            $history = QuestionGuidances::where('practice_exam_id', $this->questionGuidances->practice_exam_id)
            ->where('practice_question_id', $this->questionGuidances->practice_question_id)
            ->where('user_id', $this->questionGuidances->user_id)
            -> latest()->limit(5)->get();

            $response =  $generator->generate(
                prompt: $this->questionGuidances->prompt,
                question: $question,
                history: $history,
            );
            //store
            $this->questionGuidances->update(['response' => $response]);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        throw $exception;
    }
}
