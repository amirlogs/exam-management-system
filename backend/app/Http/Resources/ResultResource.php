<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
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
            'student_id' => $this->student_id,
            'course_offering_id' => $this->course_offering_id,
            'total_score' => $this->total_score,
            'letter_grade' => $this->letter_grade,
            'status' => $this->status,
            'published_by' => $this->published_by,
            'published_at' => $this->published_at?->format('M d, Y H:i'),
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
                    'section' => $this->student->section ? [
                        'id' => $this->student->section->id,
                        'name' => $this->student->section->name,
                    ] : null,
                ];
            }),
            'course_offering' => $this->whenLoaded('courseOffering', function () {
                return [
                    'id' => $this->courseOffering->id,
                    'course' => $this->courseOffering->course ? [
                        'id' => $this->courseOffering->course->id,
                        'code' => $this->courseOffering->course->code,
                        'title' => $this->courseOffering->course->title,
                    ] : null,
                    'semester' => $this->courseOffering->semester ? [
                        'id' => $this->courseOffering->semester->id,
                        'name' => $this->courseOffering->semester->name,
                    ] : null,
                ];
            }),
            'publisher' => $this->whenLoaded('publisher', function () {
                return [
                    'id' => $this->publisher->id,
                    'full_name' => $this->publisher->full_name,
                    'email' => $this->publisher->email,
                ];
            }),
            'created_at' => $this->created_at?->format('M d, Y H:i'),
            'updated_at' => $this->updated_at?->format('M d, Y H:i'),
        ];
    }
}
