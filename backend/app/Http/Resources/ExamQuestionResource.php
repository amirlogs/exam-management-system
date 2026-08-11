<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
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
            'exam_question_id' => $this->pivot->id,
            'course_id' => $this->course_id,
            'created_by' => $this->created_by,
            'import_history_id' => $this->import_history_id,
            'type' => $this->type,
            'chapter' => $this->chapter,
            'content' => $this->content,
            'difficulty' => $this->difficulty,
            'status' => $this->status,
            'order_number' => $this->pivot->order_number,
            'marks' => $this->pivot->marks,
            'options' => $this->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'content' => $option->option_text,
                ];
            }),
        ];
    }
}
