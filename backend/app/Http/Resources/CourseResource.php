<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'credit_hours' => $this->credit_hours,
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
            'department' => new DepartmentResource($this->whenLoaded('department')),
        ];
    }
}
