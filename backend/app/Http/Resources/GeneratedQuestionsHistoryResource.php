<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneratedQuestionsHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'input'               => $this->input,
            'count'               => (int) $this->count,
            'is_note'             => (bool) $this->isNote,
            'type'                => $this->type,
            'difficulty'          => $this->difficulty,
            'status'              => $this->status,
            'context'             => $this->context,
            'total_rows'          => (int) $this->total_rows,
            'valid_count'         => (int) $this->valid_count,
            'error_count'         => (int) $this->error_count,
            'questions'           => $this->validated_question ?? [],
            'uploaded_by'         => [
                'id'    => $this->user?->id,
                'name'  => $this->user?->full_name ?? $this->user?->name,
                'email' => $this->user?->email,
            ],
            'created_at'          => $this->created_at?->format('M d, Y H:i:s'),
            'updated_at'          => $this->updated_at?->format('M d, Y H:i:s'),
        ];
    }
}
