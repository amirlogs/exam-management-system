<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PracticeExamResource extends JsonResource
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
                'title' => $this->title,
                'duration_minutes' => $this->duration_minutes,
                'composition' => $this->composition,
                'total_marks' => $this->total_marks,
                'total_questions' => $this->total_questions,
                'status' => $this->status,
                'owner' => [
                    'id' => $this->owned_by,
                    'name' => $this->ownedBy->first_name,
                    'email' => $this->ownedBy->email,
                ],
                'created_at' => $this->created_at?->format('M d, Y'),
                'updated_at' => $this->updated_at?->format('M d, Y'),
            ];
    }
}
