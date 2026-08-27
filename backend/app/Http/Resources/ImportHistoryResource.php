<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'context' => $this->context,
            'year_level' => $this->context['year_level'] ?? null,
            'semester_id' => $this->context['semester_id'] ?? null,
            'total_rows' => $this->total_rows,
            'valid_count' => $this->valid_count,
            'error_count' => $this->error_count,
            'validated_data' => collect($this->validated_data ?? [])->map(function ($row, $rowNumber) {
                return [
                    'row_number' => (int) $rowNumber,
                    'data' => $row['data'] ?? [],
                    'status' => $row['status'] ?? 'invalid',
                    'errors' => $row['errors'] ?? [],
                ];
            })->values()->all(),
            'file_path' => $this->file_path,
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
            'uploaded_by' => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->full_name,
                'email' => $this->uploader?->email,
            ],
        ];
    }
}
