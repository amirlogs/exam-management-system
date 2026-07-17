<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Jobs\QuestionRowImport;
use App\Models\Course;
use App\Models\QuestionBankImport;

class QuestionBankImportController extends Controller
{
    public function store(string $courseId, StoreQuestionRequest $request)
    {
        $validated = $request->validated();

        // check the course if found
        if (! Course::find($courseId)) {
            return $this->error('', 'Course not found', 404);
        }

        // store the file
        $path = $request->file('file')->storeAs('imports', uniqid().'.csv');

        $import = QuestionBankImport::create([
            'course_id' => $courseId,
            'file_path' => $path,
            'status' => 'pending',
            'uploaded_by' => request()->user()->id,
        ]);

        // call the queue job

        QuestionRowImport::dispatch($import->id);

        return $this->success(['import_id' => $import->id], 'Question bank import created successfully');
    }
}
