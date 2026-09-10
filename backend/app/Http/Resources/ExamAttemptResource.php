<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'student_id' => $this->student_id,
            'status' => $this->status,
            'score' => $this->score,
            'started_at' => $this->started_at?->format('M d, Y H:i'),
            'submitted_at' => $this->submitted_at?->format('M d, Y H:i'),
            'answers_count' => $this->answers_count,
            'graded_answers_count' => $this->graded_answers_count,
            'pending_grading_count' => $this->pending_grading_count,
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'student_number' => $this->student->student_number,
                    'user' => $this->student->user ? [
                        'id' => $this->student->user->id,
                        'full_name' => $this->student->user->full_name,
                        'first_name' => $this->student->user->first_name,
                        'last_name' => $this->student->user->last_name,
                        'email' => $this->student->user->email,
                    ] : null,
                ];
            }),
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
        ];
    }
}
