<?php

namespace App\Jobs;

use App\Imports\RowCollectionImport;
use App\Models\ImportHistory;
use App\Services\ImportValidatorFactory;
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
        $validatorClass = ImportValidatorFactory::create($this->importHistory->type);

        if (! $validatorClass) {
            $this->importHistory->update(['status' => 'failed']);

            return;
        }

        $context = $this->importHistory->context ?? [];
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
            $errors = $validatorClass::validate($data, $context);
            $status = $errors ? 'invalid' : 'valid';
            $errors ? $errorCount++ : $validCount++;

            $result[$index + 2] = [
                'data' => $data,
                'status' => $status,
                'errors' => $errors,
            ];
        }

        $this->addBatchDuplicateErrors($result, $this->importHistory->type);
        $validCount = 0;
        $errorCount = 0;

        foreach ($result as $row) {
            if ($row['status'] === 'valid') {
                $validCount++;
            } else {
                $errorCount++;
            }
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

    private function addBatchDuplicateErrors(array &$rows, string $type): void
    {
        $fields = match ($type) {
            'students' => ['email', 'student_number'],
            'instructors' => ['email', 'employee_number'],
            'users' => ['email'],
            default => [],
        };

        foreach ($fields as $field) {
            $seen = [];

            foreach ($rows as $rowNumber => &$row) {
                $value = $row['data'][$field] ?? null;

                if ($value === null || trim((string) $value) === '') {
                    continue;
                }

                $normalized = strtolower(
                    trim((string) $value)
                );

                if (isset($seen[$normalized])) {
                    $previousRow = $seen[$normalized];

                    $row['errors'][] = "Duplicate {$field} '{$value}' found. It is also used in row {$previousRow}.";
                    $row['status'] = 'invalid';
                } else {
                    $seen[$normalized] = $rowNumber;
                }
            }

            unset($row);
        }

        if ($type === 'sections') {
            $seenSections = [];

            foreach ($rows as $rowNumber => &$row) {
                $programCode = strtolower(
                    trim((string) ($row['data']['program_code'] ?? ''))
                );

                $yearLevel = trim(
                    (string) ($row['data']['year_level'] ?? '')
                );

                $name = strtolower(
                    trim((string) ($row['data']['name'] ?? ''))
                );

                if ($programCode === '' || $yearLevel === '' || $name === '') {
                    continue;
                }

                $key = "{$programCode}|{$yearLevel}|{$name}";

                if (isset($seenSections[$key])) {
                    $previousRow = $seenSections[$key];

                    $row['errors'][] = "Duplicate section '{$row['data']['name']}' found for program {$row['data']['program_code']}, year {$yearLevel}. It is also used in row {$previousRow}.";

                    $row['status'] = 'invalid';
                } else {
                    $seenSections[$key] = $rowNumber;
                }
            }

            unset($row);
        }
    }

    // failed job
    public function failed(?Throwable $exception): void
    {
        $this->importHistory->update([
            'status' => 'failed',
        ]);
    }
}
