<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GradeExamJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Exam $exam, public User $user) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $chooseMark = $this->exam->composition['MCQ']['marks_each'];
        $trueFalseMark = $this->exam->composition['TRUE_FALSE']['marks_each'];
        $attempts = $this->exam->attempts()->get();

        $allExamAnswers = [];
        foreach ($attempts as $attempt) {
            $answers = $attempt->answers()->where('selected_option_id', '!=', null)->get();
            $userId = $attempt->student->user_id;
            $allExamAnswers[$userId] = $answers;
        }

        // map through below to make easier i will choose the first()
        // deciding mark is depending on the type
        foreach ($allExamAnswers as $key => $studentAnswers) {
            foreach ($studentAnswers as $answer) {
                if ($answer->isCorrect()) {
                    if ($answer->question->type === 'MCQ') {
                        $answer->create([
                            'is_correct' => true,
                            'marks_awarded' => $chooseMark,
                            'graded_by' => $this->user->id,
                            'graded_at' => now(),
                        ]);
                    } else {
                        $answer->create([
                            'is_correct' => true,
                            'marks_awarded' => $trueFalseMark,
                            'graded_by' => $this->user->id,
                            'graded_at' => now(),
                        ]);
                    }
                } else {
                    $answer->create([
                        'is_correct' => false,
                        'marks_awarded' => 0,
                        'graded_by' => $this->user->id,
                        'graded_at' => now(),
                    ]);
                }
            }
        }

    }
}
