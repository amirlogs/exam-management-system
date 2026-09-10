<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
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
            'title' => $this->title,
            'course_offering_id' => $this->course_offering_id,
            'type' => $this->type,
            'duration_minutes' => $this->duration_minutes,
            'composition' => $this->composition,
            'status' => $this->status,
            'scheduled_start' => $this->scheduled_start?->format('M d, Y'),
            'scheduled_end' => $this->scheduled_end?->format('M d, Y'),
            'review_cycle' => $this->review_cycle,
            'current_review_id' => $this->current_review_id,
            'grading_status' => $this->grading_status,
            'total_marks' => $this->total_marks,
            'total_questions' => $this->total_questions,
            'creator' => [
                'id' => $this->created_by,
                'name' => $this->creator->first_name,
                'email' => $this->creator->email,
            ],
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
        ];
    }
}
