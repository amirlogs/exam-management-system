<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // $table->unsignedSmallInteger('entry_year');
        // $table->string('status')->default('active');
        return [
            'id' => $this->id,
            'student_number' => $this->student_number,
            'program_id' => $this->program_id,
            'curriculum_id' => $this->curriculum_id,
            'entry_year' => $this->entry_year,
            'status' => $this->status,

            'user' => new UserResource($this->whenLoaded('user')),
            'section' => new SectionResource($this->whenLoaded('section')),

            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
        ];
    }
}
