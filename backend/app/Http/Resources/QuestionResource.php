<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'type' => $this->type,
            'chapter' => $this->chapter,
            'content' => $this->content,
            'difficulty' => $this->difficulty,
            'status' => $this->status,
            'total_mark'=> $this->total_mark,
            'total_questions' => $this->total_questions,
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($o) => [
                'id' => $o->id,
                'option_text' => $o->option_text,
                'is_correct' => $o->is_correct,
            ])),
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
        ];
    }
}
