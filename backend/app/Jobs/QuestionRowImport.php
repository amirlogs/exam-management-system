<?php

namespace App\Jobs;

use App\Imports\RowCollectionImport;
use App\Models\QuestionBankImport;
use App\Validation\ValidateQuestionRow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

use function Illuminate\Log\log;

class QuestionRowImport implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $importId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // find the import
        $import = QuestionBankImport::find($this->importId);
        if (! $import) {
            log()->error('Import not found', ['import_id' => $this->importId]);
            return;
        }

        // extract the file
        $sheets = Excel::toCollection(new RowCollectionImport, $import->file_path)->first();
        $rows = $sheets->first();

        $result = [];
        $validCount = 0;
        $errorCount = 0;

        foreach ($sheets as $index => $row) {
            $data = array_map(
                fn ($value) => is_bool($value) ? ($value ? 'True' : 'False') : (is_null($value) ? null : (string) $value),
                $row->toArray()
            );
            $errors = ValidateQuestionRow::validate($data);
            $status = $errors ? 'invalid' : 'valid';
            $errors ? $errorCount++ : $validCount++;

            $result[] = [
                'row' => $index + 2,
                'data' => $data,
                'status' => $status,
                'errors' => $errors,
            ];
        }
        $import->update([
            'status' => 'ready_for_review',
            'validated_data' => $result,
            'valid_count' => $validCount,
            'error_count' => $errorCount,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        QuestionBankImport::where('id', $this->importId)->update([
            'status' => 'failed',
            'failure_reason' => $exception->getMessage(),
        ]);
    }
}
