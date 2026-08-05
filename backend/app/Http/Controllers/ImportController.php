<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImportRequest;
use App\Jobs\ImportCsv;
use App\Models\ImportHistory;
use App\Services\ImportCommitterFactory;
use App\Services\ImportValidatorFactory;
use App\Validation\StudentValidator;
use App\Validation\ValidateImportContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function store(StoreImportRequest $request, string $type)
    {
        $validated = $request->validated();
        $path = $validated['file']->storeAs('imports', uniqid().'.csv');
        $context = json_decode($validated['context'], true);

        $errors = ValidateImportContext::validate($validated['type'], $context);
        if ($errors) {
            return $this->error($errors, 'Invalid context for this import type', 422);
        }

        $import = ImportHistory::create([
            'uploaded_by' => $request->user()->id,
            'context' => $context,
            'file_path' => $path,
            'type' => $validated['type'],
            'status' => 'pending',
        ]);

        // dispatch the job
        ImportCsv::dispatch($import);

        return $this->success($import, 'Student import started successfully');
    }

    public function show(ImportHistory $importHistory)
    {
        return $this->success($importHistory, 'Import history retrieved successfully');
    }

    public function update(ImportHistory $importHistory, string $rowIndex, Request $request)
    {
        $errors = StudentValidator::UpdateValidation($request->all());

        if ($errors) {
            return $this->error($errors, 'Validation error', 422);
        }

        if ($importHistory->status !== 'ready_for_review') {
            return $this->error([], "Import history is in {$importHistory->status} state, cannot update row$", 400);
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

        DB::transaction(function () use ($importHistory, $committerClass) {
            foreach ($importHistory->validated_data as $row) {
                $committerClass::commit($row['data'], $importHistory->context);
            }

            $importHistory->update(['status' => 'confirmed']);
        });

        return $this->success($importHistory->fresh(), 'Import confirmed successfully');
    }
}
