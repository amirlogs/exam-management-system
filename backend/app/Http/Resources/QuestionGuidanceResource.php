<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionGuidanceResource extends JsonResource
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
            'user_id' => $this->user_id,
            'student_id' => $this->student_id,
            'practice_exam_id' => $this->practice_exam_id,
            'practice_question_id' => $this->practice_question_id,
            'prompt' => $this->prompt,
            'response' => $this->response,
            'created_at' => $this->created_at?->format('M d , Y'),
            'updated_at' => $this->updated_at?->format('M d , Y'),
        ];
    }
}
