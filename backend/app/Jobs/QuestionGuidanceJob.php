<?php

namespace App\Jobs;

use App\Models\PracticeAnswers;
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
            if ($question) {
                $question->loadMissing('options');
            }

            // Retrieve the student's answer for this question if attempted
            $studentAnswer = PracticeAnswers::where('practice_exam_id', $this->questionGuidances->practice_exam_id)
                ->where('practice_question_id', $this->questionGuidances->practice_question_id)
                ->where('user_id', $this->questionGuidances->user_id)
                ->first();

            // Retrieve previous completed guidance history in chronological order
            $history = QuestionGuidances::where('practice_exam_id', $this->questionGuidances->practice_exam_id)
                ->where('practice_question_id', $this->questionGuidances->practice_question_id)
                ->where('user_id', $this->questionGuidances->user_id)
                ->where('id', '<', $this->questionGuidances->id)
                ->whereNotNull('response')
                ->latest()
                ->limit(5)
                ->get()
                ->reverse()
                ->values();

            $response = $generator->generate(
                prompt: $this->questionGuidances->prompt,
                question: $question,
                history: $history,
                studentAnswer: $studentAnswer,
            );

            // Store response
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
