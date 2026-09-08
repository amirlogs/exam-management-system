<?php

namespace App\Jobs;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\Grade;
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
        $exam = $this->exam->load(['examQuestions.question.options']);
        $examQuestions = $exam->examQuestions->keyBy('id');

        $attempts = $exam->attempts()
            ->whereIn('status', ['completed', 'auto_submitted', 'in_progress'])
            ->get();

        foreach ($attempts as $attempt) {
            // Auto-submit in_progress attempts if exam has completed
            if ($attempt->status === 'in_progress') {
                $attempt->update([
                    'status' => 'auto_submitted',
                    'submitted_at' => $attempt->submitted_at ?? now(),
                ]);
            }

            $answers = $attempt->answers()->with(['selectedOption', 'question'])->get();

            foreach ($answers as $answer) {
                $examQuestion = $examQuestions->get($answer->exam_question_id);
                $qMarks = (float) ($examQuestion?->marks ?? 0);
                $questionType = strtoupper($answer->question?->type ?? '');

                if (in_array($questionType, ['MCQ', 'TRUE_FALSE'])) {
                    $isCorrect = $answer->selectedOption?->is_correct === true;
                    $marksAwarded = $isCorrect ? $qMarks : 0;

                    $answer->update([
                        'is_correct' => $isCorrect,
                        'marks_awarded' => $marksAwarded,
                        'graded_by' => $this->user->id,
                        'graded_at' => now(),
                    ]);
                }
            }

            // Calculate total marks awarded for this attempt
            $totalAttemptScore = (float) $attempt->answers()->whereNotNull('marks_awarded')->sum('marks_awarded');
            $hasUngraded = $attempt->answers()->whereNull('marks_awarded')->exists();

            $attempt->update([
                'score' => $totalAttemptScore,
                'status' => $hasUngraded ? 'completed' : 'graded',
            ]);

            Grade::updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'student_id' => $attempt->student_id,
                    'course_offering_id' => $exam->course_offering_id,
                ],
                [
                    'score' => $totalAttemptScore,
                    'graded_by' => $this->user->id,
                    'status' => 'pending_verification',
                ]
            );
        }

        $pendingManualCount = Answer::whereHas('examAttempt', fn ($q) => $q->where('exam_id', $exam->id))
            ->whereNull('marks_awarded')
            ->count();

        $exam->update([
            'grading_status' => $pendingManualCount > 0 ? 'in_progress' : 'completed',
        ]);
    }
}
