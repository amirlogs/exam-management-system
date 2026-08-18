<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseOfferingSuggestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'course_code' => $this['course_code'],
            'course_name' => $this['course_name'],
            'program_id' => $this['program_id'],
            'program_name' => $this['program_name'],
            'year_level' => $this['year_level'],
        ];
    }
}
