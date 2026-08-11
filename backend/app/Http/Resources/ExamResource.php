<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
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
            'course_offering_id' => $this->course_offering_id,
            'type' => $this->type,
            'duration_minutes' => $this->duration_minutes,
            'composition' => $this->composition,
            'status' => $this->status,
            'scheduled_start' => $this->scheduled_start,
            'scheduled_end' => $this->scheduled_end,
        ];
    }
}
