<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SemesterResource extends JsonResource
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
            'name' => $this->name,
            'start_date' => $this->start_date?->format('M d, Y'),
            'end_date' => $this->end_date?->format('M d, Y'),
            'academic_year' => $this->academic_year,
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
            'courses' => CourseResource::collection($this->whenLoaded('courses')), // Include courses if loaded
        ];
    }
}
