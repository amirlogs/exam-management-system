<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurriculumResource extends JsonResource
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
            'program_id' => $this->program_id,
            'version' => $this->version,
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
            'courses' => CourseResource::collection($this->whenLoaded('courses')),
        ];
    }
}
