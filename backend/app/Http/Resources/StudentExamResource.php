<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attempt = $this->whenLoaded('attempts', function () {
            $latestAttempt = $this->attempts->first();
            if (! $latestAttempt) {
                return null;
            }

            return [
                'id' => $latestAttempt->id,
                'status' => $latestAttempt->status,
                'started_at' => $latestAttempt->started_at?->format('M d, Y H:i'),
                'submitted_at' => $latestAttempt->submitted_at?->format('M d, Y H:i'),
                'score' => $latestAttempt->score,
            ];
        });

        return [
            'id' => $this->id,
            'course_offering_id' => $this->course_offering_id,
            'title' => $this->title,
            'type' => $this->type,
            'duration_minutes' => $this->duration_minutes,
            'total_marks' => $this->total_marks,
            'total_questions' => $this->total_questions,
            'status' => $this->status,
            'scheduled_start' => $this->scheduled_start?->format('M d, Y H:i'),
            'scheduled_end' => $this->scheduled_end?->format('M d, Y H:i'),
            'course' => $this->courseOffering?->course ? [
                'id' => $this->courseOffering->course->id,
                'code' => $this->courseOffering->course->code,
                'name' => $this->courseOffering->course->name,
            ] : null,
            'semester' => $this->courseOffering?->semester ? [
                'id' => $this->courseOffering->semester->id,
                'name' => $this->courseOffering->semester->name,
            ] : null,
            'attempt' => $attempt,
            'created_at' => $this->created_at?->format('M d, Y'),
        ];
    }
}
