<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseInstructorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_offering_id' => $this->course_offering_id,
            'section_id' => $this->section_id,
            'instructor_id' => $this->instructor_id,
            'type' => $this->type,
            'assigned_at' => $this->assigned_at?->format('M d, Y'),

            'instructor' => new InstructorResource($this->whenLoaded('instructor')),
            'section' => new SectionResource($this->whenLoaded('section')),
        ];
    }
}
