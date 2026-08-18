<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportHistoryResource extends JsonResource
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
        'type' => $this->type,
        'status' => $this->status,
        'year_level' => $this->year_level,
        'semester_id' => $this->semester_id,
        'file_path' => $this->file_path,
        'created_at' => $this->created_at?->format('M d, Y'),
        'updated_at' => $this->updated_at?->format('M d, Y'),
        'uploded_dy'=> [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
                'email' => $this->uploader->email,
            ],
        ];
    }
}
