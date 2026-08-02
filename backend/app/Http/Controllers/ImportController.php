<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImportRequest;
use App\Jobs\ImportCsv;
use App\Models\ImportHistory;

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
    
}
