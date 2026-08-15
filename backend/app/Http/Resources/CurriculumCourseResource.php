<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurriculumCourseResource extends JsonResource
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
            'curriculum_id' => $this->curriculum_id,
            'course_id' => $this->course_id,
            'year_level' => $this->year_level,
            'semester_number' => $this->semester_number,
            'created_at' => $this->created_at?->format('M d, Y'),
            'deleted_at' => $this->deleted_at?->format('M d, Y'),
            'course' => new CourseResource($this->whenLoaded('course')),
            'curriculum' => new CurriculumResource($this->whenLoaded('curriculum')),
        ];
    }
}
