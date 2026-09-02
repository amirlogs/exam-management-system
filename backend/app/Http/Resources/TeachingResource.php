<?php

namespace App\Http\Resources;

use App\Http\Resources\CourseResource;
use App\Http\Resources\SemesterResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeachingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'semester' => new SemesterResource($this->whenLoaded('semester')),
            'status' => $this->status,

            'assignments' => $this->whenLoaded(
                'courseInstructors',
                fn() => $this->courseInstructors->map(
                    fn($assignment)
                    => [
                        'id' => $assignment->id,
                        'type' => $assignment->type,
                        'assigned_at' => $assignment->assigned_at?->format('M d, Y'),

                        'section' => [
                            'id' => $assignment->section->id,
                            'name' => $assignment->section->name,
                            'year_level' => $assignment->section->year_level,
                            'program' => [
                                'id' => $assignment->section->program->id,
                                'name' => $assignment->section->program->name,
                                'code' => $assignment->section->program->code,
                            ],
                        ],
                    ]
                )->values()
            ),
        ];
    }
}
