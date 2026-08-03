<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImportRequest;
use App\Jobs\ImportCsv;
use App\Models\ImportHistory;
use App\Validation\StudentValidator;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function store(StoreImportRequest $request)
    {

        $validated = $request->validated();

        $path = $validated['file']->storeAs('imports', uniqid().'.csv');

        $import = ImportHistory::create([
            'uploaded_by' => $request->user()->id,
            'file_path' => $path,
            'type' => 'student', // this has to be dynamic
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
        $errors = StudentValidator::validate($request->all());

        if ($errors) {
            return $this->error($errors, 'Validation error', 422);
        }

        if ($importHistory->status !== 'ready_for_review') {
            return $this->error([], "Import history is in {$importHistory->status} state, cannot update row$", 400);
        }
        $rows = $importHistory['validated_data'];
        $status = $rows[$rowIndex]['status'];
        $data = [
            'row' => $rowIndex,
            'data' => $request->all(),
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

    public function confirm(ImportHistory $importHistory) {
       // Todo: map base on the type and store  
    }
}
