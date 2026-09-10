<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
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
            'exam_id' => $this->exam_id,
            'score' => $this->score,
            'status' => $this->status,
            'graded_by' => $this->graded_by,
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
            'exam' => $this->whenLoaded('exam', function () {
                return [
                    'id' => $this->exam->id,
                    'title' => $this->exam->title,
                    'type' => $this->exam->type,
                    'total_marks' => $this->exam->total_marks,
                    'status' => $this->exam->status,
                ];
            }),
            'grader' => $this->whenLoaded('grader', function () {
                return [
                    'id' => $this->grader->id,
                    'full_name' => $this->grader->full_name,
                    'email' => $this->grader->email,
                ];
            }),
            'verifications' => $this->whenLoaded('verifications', function () {
                return $this->verifications->map(function ($verification) {
                    return [
                        'id' => $verification->id,
                        'status' => $verification->status,
                        'comment' => $verification->comment,
                        'verified_at' => $verification->verified_at?->format('M d, Y H:i'),
                        'verifier' => $verification->verifier ? [
                            'id' => $verification->verifier->id,
                            'full_name' => $verification->verifier->full_name,
                            'email' => $verification->verifier->email,
                        ] : null,
                    ];
                });
            }),
            'created_at' => $this->created_at?->format('M d, Y H:i'),
            'updated_at' => $this->updated_at?->format('M d, Y H:i'),
        ];
    }
}
