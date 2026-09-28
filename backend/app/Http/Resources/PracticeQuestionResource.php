<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PracticeQuestionResource extends JsonResource
{
    public function toArray($request): array
    {
        // 1. Define the base array
        $data = [
            'id' => $this->id,
            'owned_by' => $this->owned_by,
            'type' => $this->type,
            'practice_question_histories_id' => $this->practice_question_histories_id,
            'content' => $this->content,
            'difficulty' => $this->difficulty,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
        ];

        if ($this->relationLoaded('optionsWithoutAnswers')) {
            $data['options'] = $this->optionsWithoutAnswers->map(fn($o) => [
                'id' => $o->id,
                'option_text' => $o->option_text,
            ]);
        } elseif ($this->relationLoaded('options')) {
            $data['options'] = $this->options->map(fn($o) => [
                'id' => $o->id,
                'option_text' => $o->option_text,
                'is_correct' => $o->is_correct,
            ]);
        }

        $user = $request->user();
        $userAnswer = $user ? $this->answers()->where('user_id', $user->id)->first() : null;
        $data['user_answer'] = $userAnswer ? [
            'id' => $userAnswer->id,
            'selected_option_id' => $userAnswer->selected_option_id,
            'is_correct' => (bool) $userAnswer->is_correct,
            'answer_text' => $userAnswer->answer_text,
        ] : null;

        return $data;
    }
}
