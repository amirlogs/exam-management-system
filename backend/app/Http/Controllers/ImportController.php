<?php

namespace App\Http\Controllers;

use App\Commit\ExamQuestionCommitter;
use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\CsvImportResource;
use App\Http\Resources\ImportHistoryResource;
use App\Jobs\ImportCsv;
use App\Models\Exam;
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

        return $this->success(new ImportHistoryResource($import), 'Student import started successfully');
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

    public function update(ImportHistory $importHistory, string $rowIndex, Request $request)
    {

        if ($importHistory->status !== 'ready_for_review') {
            return $this->error([], "Import history is in {$importHistory->status} state, cannot update row", 400);
        }

        $validatorClass = ImportValidatorFactory::create($importHistory->type);
        $errors = $validatorClass::UpdateValidation($request->all(), $importHistory->context);

        if ($errors) {
            return $this->error($errors, 'Validation error', 422);
        }

        // / loop and update only the comming rows
        $rows = $importHistory['validated_data'];
        $status = $rows[$rowIndex]['status'];
        // merge the comming with the old one and prioritize the comming one
        $updatedData = array_merge($rows[$rowIndex]['data'], $request->all());
        $data = [
            'row' => $rowIndex,
            'data' => $updatedData,
            'status' => 'valid',
            'errors' => [],
        ];
        $rows[$rowIndex] = $data;

        if ($status === 'invalid') {
            $validCount = $importHistory->valid_count + 1;
            $errorCount = $importHistory->error_count - 1;
        } else {
            $validCount = $importHistory->valid_count;
            $errorCount = $importHistory->error_count;
        }

        $importHistory->update([
            'validated_data' => $rows,
            'valid_count' => $validCount,
            'error_count' => $errorCount,
        ]);

        return $this->success($importHistory, 'Row updated successfully');
    }

    public function confirm(ImportHistory $importHistory)
    {
        $validatorClass = ImportValidatorFactory::create($importHistory->type);
        foreach ($importHistory->validated_data as $row) {
            $errors = $validatorClass::validate($row['data'], $importHistory->context);

            if ($errors) {
                return $this->error($errors, 'All rows must be valid before confirming', 422);
            }
        }

        // transaction update and map the data
        $committerClass = ImportCommitterFactory::create($importHistory->type);
        // $examId = $context['exam_id'] ?? null;
        $examId = $importHistory->context['exam_id'] ?? null;
        $exam = $examId ? Exam::find($examId) : null;

        DB::transaction(function () use ($importHistory, $committerClass, $exam) {
            foreach ($importHistory->validated_data as $row) {
                $committerClass::commit($row['data'], $importHistory);
            }

            $importHistory->update(['status' => 'confirmed']);

            if ($exam) {
                $questions = Question::where('import_history_id', $importHistory->id)->get();
                foreach ($questions as $question) {
                    ExamQuestionCommitter::commit($question, $exam);
                }
            }
        });

        return $this->success($importHistory->fresh(), 'Import confirmed successfully');
    }

    public function destroy(ImportHistory $importHistory, string $rowIndex, Request $request)
    {
        if ($importHistory->status !== 'ready_for_review') {
            return $this->error([], "Import history is in {$importHistory->status} state, cannot delete row", 400);
        }

        $data = $importHistory->validated_data;
        $status = $data[$rowIndex]['status'];
        unset($data[$rowIndex]);

        if ($status === 'invalid') {
            $errorCount = $importHistory->error_count - 1;
            $validCount = $importHistory->valid_count;
        } else {
            $validCount = $importHistory->valid_count + 1;
            $errorCount = $importHistory->error_count;
        }

        $importHistory->update([
            'validated_data' => $data,
            'valid_count' => $validCount,
            'error_count' => $errorCount,
        ]);

        return $this->success($importHistory, 'Row deleted successfully');
    }
}
