<?php

namespace App\Http\Controllers;

use App\Commit\ExamQuestionCommitter;
use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\CsvImportResource;
use App\Http\Resources\ImportHistoryResource;
use App\Jobs\ImportCsv;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ImportHistory;
use App\Models\Question;
use App\Services\ImportCommitterFactory;
use App\Services\ImportValidatorFactory;
use App\Validation\GetRequestsValidator;
use App\Validation\ValidateImportContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    private function recalculateRowCounts(array $rows): array
    {
        $validCount = 0;
        $errorCount = 0;

        foreach ($rows as $row) {
            if (($row['status'] ?? 'invalid') === 'valid') {
                $validCount++;
            } else {
                $errorCount++;
            }
        }

        return [
            'total_rows' => count($rows),
            'valid_count' => $validCount,
            'error_count' => $errorCount,
        ];
    }

    private function validateBatchDuplicates(string $type, array $rows): array
    {
        $errors = [];

        $fields = match ($type) {
            'students' => ['email', 'student_number'],
            'instructors' => ['email', 'employee_number'],
            'users' => ['email'],
            default => [],
        };

        foreach ($fields as $field) {
            $seen = [];

            foreach ($rows as $rowNumber => $row) {
                $value = $row['data'][$field] ?? null;

                if ($value === null || trim((string) $value) === '') {
                    continue;
                }

                $normalized = strtolower(trim((string) $value));

                if (isset($seen[$normalized])) {
                    $previousRow = $seen[$normalized];
                    $errors[$rowNumber][] = "Duplicate {$field} '{$value}' found. It is also used in row {$previousRow}.";
                } else {
                    $seen[$normalized] = $rowNumber;
                }
            }
        }

        if ($type === 'sections') {
            $seenSections = [];

            foreach ($rows as $rowNumber => $row) {
                $programCode = strtolower(trim((string) ($row['data']['program_code'] ?? '')));

                $yearLevel = trim((string) ($row['data']['year_level'] ?? ''));

                $name = strtolower(trim((string) ($row['data']['name'] ?? '')));

                if ($programCode === '' || $yearLevel === '' || $name === '') {
                    continue;
                }

                $key = "{$programCode}|{$yearLevel}|{$name}";

                if (isset($seenSections[$key])) {
                    $previousRow = $seenSections[$key];

                    $errors[$rowNumber][] = "Duplicate section '{$row['data']['name']}' found for program {$row['data']['program_code']}, year {$yearLevel}. It is also used in row {$previousRow}.";
                } else {
                    $seenSections[$key] = $rowNumber;
                }
            }
        }

        return $errors;
    }

    public function store(StoreImportRequest $request, string $type)
    {
        $validated = $request->validated();

        $context = json_decode($validated['context'], true);
        $context['uploaded_by'] = $request->user()->id;

        $errors = ValidateImportContext::validate($validated['type'], $context);

        if ($errors) {
            return $this->error($errors, 'Invalid context for this import type', 422);
        }

        $path = $validated['file']->storeAs('imports', uniqid().'.csv');

        $import = ImportHistory::create([
            'uploaded_by' => $request->user()->id,
            'context' => $context,
            'file_path' => $path,
            'type' => $validated['type'],
            'status' => 'pending',
        ]);

        // dispatch the job
        ImportCsv::dispatch($import);

        return $this->success(new ImportHistoryResource($import), 'import started successfully');
    }

    public function cancel(ImportHistory $importHistory)
    {
        if ($importHistory->status === 'confirmed') {
            return $this->error([], 'Cannot cancel an import that has already been confirmed', 400);
        }

        Storage::delete($importHistory->file_path);
        $importHistory->delete();

        return $this->success(null, 'Import cancelled successfully');
    }

    public function index(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = ImportHistory::query();
        RequestFilters::apply($query, $request, ['type', 'status']);
        $importHistory = $query->paginate($per_page);

        return $this->paginate($importHistory, CsvImportResource::class, 'Import history retrieved successfully');
    }

    public function show(ImportHistory $importHistory)
    {
        return $this->success($importHistory, 'Import history retrieved successfully');
    }

    public function update(ImportHistory $importHistory, string $rowNumber, Request $request)
    {
        if ($importHistory->status !== 'ready_for_review') {
            return $this->error(null, "Import history is in {$importHistory->status} state, cannot update row", 400);
        }

        $rows = $importHistory->validated_data ?? [];

        if (! array_key_exists($rowNumber, $rows)) {
            return $this->error(null, 'Import row not found', 404);
        }

        $validatorClass = ImportValidatorFactory::create($importHistory->type);

        $updatedData = array_merge($rows[$rowNumber]['data'] ?? [], $request->all());

        $rows[$rowNumber] = [
            'data' => $updatedData,
            'status' => 'valid',
            'errors' => [],
        ];

        $batchErrors = $this->validateBatchDuplicates($importHistory->type, $rows);

        foreach ($rows as $key => &$row) {
            $errors = $validatorClass::validate($row['data'], $importHistory->context ?? []);

            if (isset($batchErrors[$key])) {
                $errors = array_merge($errors, $batchErrors[$key]);
            }

            $row['status'] = empty($errors) ? 'valid' : 'invalid';
            $row['errors'] = $errors;
        }

        unset($row);

        $counts = $this->recalculateRowCounts($rows);

        $importHistory->update([
            'validated_data' => $rows,
            ...$counts,
        ]);

        return $this->success(new ImportHistoryResource($importHistory->refresh()), 'Row updated successfully');
    }

    public function confirm(ImportHistory $importHistory)
    {
        if ($importHistory->status !== 'ready_for_review') {
            return $this->error(null, "Import history is in {$importHistory->status} state, cannot confirm", 400);
        }

        // Check duplicate values inside this import batch
        $batchErrors = $this->validateBatchDuplicates($importHistory->type, $importHistory->validated_data ?? []);

        if ($batchErrors) {
            return $this->error($batchErrors, 'Duplicate values were found in the import', 422);
        }

        // Validate every row again before committing
        $validatorClass = ImportValidatorFactory::create($importHistory->type);

        foreach ($importHistory->validated_data ?? [] as $row) {
            $errors = $validatorClass::validate($row['data'], $importHistory->context ?? []);

            if ($errors) {
                return $this->error($errors, 'All rows must be valid before confirming', 422);
            }
        }

        $committerClass = ImportCommitterFactory::create($importHistory->type);

        $examId = $importHistory->context['exam_id'] ?? null;
        $exam = $examId ? Exam::find($examId) : null;

        DB::transaction(function () use ($importHistory, $committerClass, $exam) {
            foreach ($importHistory->validated_data ?? [] as $row) {
                $committerClass::commit($row['data'], $importHistory);
            }

            $importHistory->update([
                'status' => 'confirmed',
            ]);

            if ($exam) {
                $questions = Question::where('import_history_id', $importHistory->id)->get();
                foreach ($questions as $question) {
                    ExamQuestionCommitter::commit($question, $exam);
                }
                $examQuestions = ExamQuestion::where('exam_id', $exam->id)->get();
                $exam->update([
                    'total_marks' => $examQuestions->sum('marks'),
                    'total_questions' => $examQuestions->count(),
                ]);
            }
        });

        return $this->success(new ImportHistoryResource($importHistory->refresh()), 'Import confirmed successfully');
    }

    public function destroy(ImportHistory $importHistory, string $rowNumber, Request $request)
    {
        if ($importHistory->status !== 'ready_for_review') {
            return $this->error(null, "Import history is in {$importHistory->status} state, cannot delete row", 400);
        }

        $rows = $importHistory->validated_data ?? [];

        if (! array_key_exists($rowNumber, $rows)) {
            return $this->error(null, 'Import row not found', 404);
        }

        unset($rows[$rowNumber]);

        $counts = $this->recalculateRowCounts($rows);

        $importHistory->update(
            [
                'validated_data' => $rows,
                ...$counts,
            ]);

        return $this->success(new ImportHistoryResource($importHistory->refresh()), 'Row deleted successfully');
    }
}
