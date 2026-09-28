<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PracticeExamHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return
            [
                'id' => $this->id,
                'owned_by' => $this->owned_by,
                'input' => $this->input,
                'count' => $this->count,
                'isNote' => $this->isNote,
                'type' => $this->type,
                'difficulty' => $this->difficulty,
                'practice_exam_id' => $this->practice_exam_id,
                'file_path' => $this->file_path,
                'total_rows' => $this->total_rows,
                'valid_count' => $this->valid_count,
                'error_count' => $this->error_count,
                'validated_question' => $this->validated_question,
                'status' => $this->status,
                'updated_at' => $this->updated_at?->format('M d , Y'),
                'created_at' => $this->created_at?->format('M d , Y'),
            ];
    }
}
