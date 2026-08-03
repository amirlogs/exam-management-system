<?php

namespace App\Jobs;

use App\Imports\RowCollectionImport;
use App\Models\ImportHistory;
use App\Validation\StudentValidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportCsv implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public ImportHistory $importHistory) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sheets = Excel::toCollection(new RowCollectionImport, $this->importHistory->file_path)->first();
        $rows = $sheets->first();

        $result = [];
        $validCount = 0;
        $errorCount = 0;

        foreach ($sheets as $index => $row) {
            $data = array_map(
                fn ($value) => is_bool($value) ? ($value ? 'True' : 'False') : (is_null($value) ? null : (string) $value),
                $row->toArray()
            );
            $errors = StudentValidator::validate($data);
            $status = $errors ? 'invalid' : 'valid';
            $errors ? $errorCount++ : $validCount++;

            $result[$index + 2] = [
                'row' => $index + 2,
                'data' => $data,
                'status' => $status,
                'errors' => $errors,
            ];
        }

        $this->importHistory->update([
            'validated_data' => $result,
            'valid_count' => $validCount,
            'error_count' => $errorCount,
            'status' => 'ready_for_review',
            'total_rows' => $sheets->count(),
        ]);

        Storage::delete($this->importHistory->file_path);
    }

    // failed job
    public function failed(?Throwable $exception): void
    {
        $this->importHistory->update([
            'status' => 'failed',
        ]);
    }
}
