<?php

namespace App\Http\Controllers;

use App\Jobs\ImportCsv;
use App\Models\Course;
use App\Models\ImportHistory;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionImportController extends Controller
{
    // public function store(int $courseId, Request $request)
    // {
    //     if (! Course::find($courseId)) {
    //         return $this->error(null, 'Course not found', 404);
    //     }

    //     $request->validate(['file' => 'required|file|mimes:csv,xlsx']);
    //     $path = $request->file('file')->store('imports');

    //     $import = ImportHistory::create([
    //         'uploaded_by' => $request->user()->id,
    //         'type' => 'questions',
    //         'file_path' => $path,
    //         'context' => ['course_id' => $courseId, 'uploaded_by' => $request->user()->id],
    //         'status' => 'pending',
    //     ]);

    //     $import->update(['context' => array_merge($import->context, ['import_history_id' => $import->id])]);

    //     ImportCsv::dispatch($import);

    //     return $this->success(['import_id' => $import->id, 'status' => 'pending'], 'Question bank import queued successfully', 202);

    // }

    // public function activateImport(ImportHistory $importHistory)
    // {
    //     if ($importHistory->type !== 'questions') {
    //         return $this->error(null, 'Invalid import type', 400);
    //     }

    //     if ($importHistory->status !== 'confirmed') {
    //         return $this->error(null, 'Import must be confirmed before its questions can be activated', 409);
    //     }

    //     $activatedCount = Question::where('import_history_id', $importHistory->id)
    //         ->where('status', 'draft')
    //         ->update(['status' => 'active']);

    //     return $this->success(['activated_count' => $activatedCount], 'Questions activated successfully');
    // }

}
