<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Jobs\QuestionRowImport;
use App\Models\Course;
use App\Models\QuestionBankImport;
use App\Validation\ValidateQuestionRow;
use Illuminate\Http\Request;

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

    public function show(int $importId)
    {
        $import = QuestionBankImport::find($importId);

        if (! $import) {
            return $this->error('', 'Import not found', 404);
        }

        return $this->success($import, 'Import found');
    }

    public function update(int $importId, int $rowIndex, Request $request)
    {
        $validatederror = ValidateQuestionRow::validate($request->all());

        if (! empty($validatederror)) {
            return $this->error($validatederror, 'Validation error', 422);
        }

        $import = QuestionBankImport::find($importId);

        if (! $import) {
            return $this->error('', 'Questions import not found', 404);
        }

        // validate the data  incoming data
        $rows = $import->validated_data;
        $found = false;

        foreach ($rows as &$row) {
            if ($row['row'] === $rowIndex) {
                $row['data'] = $request->all();
                $row['status'] = 'valid';
                $row['errors'] = null;
                $row['row'] = $rowIndex;
                $found = true;
                break;
            }
        }
        unset($row);

        if (! $found) {
            return $this->error('', 'Question not found', 404);
        }

        // update the db
        $import->validated_data = $rows;
        $import->valid_count = count(array_filter($rows, fn ($r) => $r['status'] === 'valid'));
        $import->error_count = count(array_filter($rows, fn ($r) => $r['status'] === 'invalid'));
        $import->save();

        return $this->success($import, 'Question import updated successfully');
    }

    // use transaction
    public function confirm(int $importId)
    {
        // get the imported data and check if its ready for review

        // check if the error count is 0 else return error

        // if 0 then update the status and map the question | use transaction

        // return the success message
    }

    public function approve(int $importId)
    {
        // get the data and it must be confirmed

        // check if there is no falg

        // update the status to approved

        // return the success message
    }
}
