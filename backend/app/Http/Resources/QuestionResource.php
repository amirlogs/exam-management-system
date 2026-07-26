<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
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
            'course_id' => $this->course_id,
            'import_id' => $this->import_id,
            'type' => $this->type,
            'text' => $this->text,
            'options' => $this->options,
            'correct_answer' => $this->correct_answer,
            'difficulty' => $this->difficulty,
            'points' => $this->points,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
