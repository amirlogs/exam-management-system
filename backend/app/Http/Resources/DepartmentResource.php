<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
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
            'college_id' => $this->college_id,
            'name' => $this->name,
            'type' => $this->type,
            'updated_at' => $this->updated_at?->format('M d , Y'),
            'created_at' => $this->created_at?->format('M d , Y'),
            'college' => new CollegeResource($this->whenLoaded('college')),
        ];
    }
}
