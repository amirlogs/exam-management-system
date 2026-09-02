<?php

namespace App\Http\Resources;

use App\Http\Resources\CourseResource;
use App\Http\Resources\SemesterResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeachingDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assignedSectionIds = $this->courseInstructors ->pluck('section_id') ->unique();

        $students = $this->enrollments ->filter(function ($enrollment) use ($assignedSectionIds) {
            return $assignedSectionIds ->contains($enrollment->student?->section_id);
        }) ->values();


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
                        'section' => $assignment->section
                        ? [
                            'id' => $assignment->section->id,
                            'name' => $assignment->section->name,
                            'year_level' => $assignment->section->year_level,
                            'program' => $assignment->section->program ? [
                                'id' => $assignment->section->program->id,
                                'name' => $assignment->section->program->name,
                                'code' => $assignment->section->program->code,
                            ] : null,
                        ] : null,
                    ]
                )->values()
            ),

            'students' => $students->map(
                fn($enrollment) => [
                    'enrollment_id' => $enrollment->id,
                    'status' => $enrollment->status,

                    'student' => $enrollment->student ? [
                        'id' => $enrollment->student->id,
                        'student_number' => $enrollment->student->student_number,
                        'status' => $enrollment->student->status,

                        'user' => $enrollment->student->user ? [
                            'id' => $enrollment->student->user->id,
                            'first_name' => $enrollment->student->user->first_name,
                            'last_name' => $enrollment->student->user->last_name,
                            'email' => $enrollment->student->user->email,
                        ] : null,

                        'section' => $enrollment->student->section ? [
                            'id' => $enrollment->student->section->id,
                            'name' => $enrollment->student->section->name,
                            'year_level' => $enrollment->student->section->year_level,
                        ] : null,
                    ] : null,
                ]
            )->values(),

            'exams' => $this->whenLoaded(
                'exams',
                fn() => $this->exams->map(
                    fn($exam) => [
                        'id' => $exam->id,
                        'title' => $exam->title,
                        'type' => $exam->type,
                        'duration_minutes' => $exam->duration_minutes,
                        'status' => $exam->status,
                        'total_marks' => $exam->total_marks,
                        'total_questions' => $exam->total_questions,
                        'scheduled_start' => $exam->scheduled_start,
                        'scheduled_end' => $exam->scheduled_end,
                    ]
                )->values()
            ),
        ];
    }
}
