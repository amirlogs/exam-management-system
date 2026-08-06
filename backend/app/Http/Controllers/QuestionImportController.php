<?php

namespace App\Http\Controllers;

use App\Jobs\ImportCsv;
use App\Models\Course;
use App\Models\ImportHistory;
use Illuminate\Http\Request;

class QuestionImportController extends Controller
{
    public function store(int $courseId, Request $request)
    {

        if (! Course::find($courseId)) {
            return $this->error(null, 'Course not found', 404);
        }

        $request->validate(['file' => 'required|file|mimes:csv,xlsx']);
        $path = $request->file('file')->store('imports');

        $import = ImportHistory::create([
            'uploaded_by' => $request->user()->id,
            'type' => 'questions',
            'file_path' => $path,
            'context' => ['course_id' => $courseId, 'uploaded_by' => $request->user()->id],
            'status' => 'pending',
        ]);

        ImportCsv::dispatch($import);

        return $this->success(['import_id' => $import->id, 'status' => 'pending'], 'Question bank import queued successfully', 202);
    }
}
