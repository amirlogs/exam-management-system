<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Jobs\QuestionRowImport;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionBankImport;
use App\Models\QuestionFlag;
use App\Validation\ValidateQuestionRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportQuestionController extends Controller
{
    public function index()
    {
        $import = QuestionBankImport::all()->makeHidden(['validated_data']);

        if ($import->isEmpty()) {
            return $this->error('', 'Import not found', 404);
        }

        return $this->success($import, 'Import found');
    }

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

        if ($import->status === 'pending') {
            return $this->error('', 'Import in progress, please wait for sometime to process ', 400);
        }

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

    public function confirm(int $importId)
    {
        $import = QuestionBankImport::find($importId);
        if (! $import) {
            return $this->error('', 'Question import not found', 404);
        }
        if ($import->status !== 'ready_for_review') {
            if ($import->status === 'confirmed') {
                return $this->error('', 'Question import already confirmed', 400);
            }

            return $this->error('', 'Question import is not ready for review status', 400);
        }

        if ($import->error_count > 0) {
            return $this->error('', 'Question import has errors fix them first', 400);
        }
        $data = $import->validated_data;

        DB::transaction(function () use ($import, $data) {
            foreach ($data as $row) {
                $row = $row['data'];
                Question::create([
                    'course_id' => $import->course_id,
                    'import_id' => $import->id,
                    'text' => $row['text'],
                    'options' => $row['options'] ?? null,
                    'type' => $row['type'],
                    'correct_answer' => $row['correct_answer'],
                    'difficulty' => $row['difficulty'],
                    'points' => $row['points'],
                    'status' => 'confirmed',
                    'is_active' => false,
                ]);
            }
            $import->update([
                'status' => 'confirmed',
                'confirmed_by' => request()->user()->id,
                'confirmed_at' => now(),
            ]);
        }, 3);

        return $this->success(['count' => count($data), 'status' => 'confirmed'], 'Question import confirmed successfully');
    }

    public function approve(int $importId)
    {
        // get the data and it must be confirmed

        // check if there is no falg

        // update the status to approved

        // return the success message
    }

    public function flags(int $importId)
    {
        $import = QuestionBankImport::find($importId);
        if (! $import) {
            return $this->error('Question import not found', 404);
        }
        $flags = QuestionFlag::whereIn('question_id', function ($query) use ($importId) {
            $query->select('id')
                ->from('questions')
                ->where('import_id', $importId);
        })
            ->with([
                'instructor.user:id,name',
            ])
            ->latest()
            ->get();

        $response = [
            'import_id' => $import->id,
            'open_count' => $flags->where('status', 'open')->count(),
            'resolved_count' => $flags->where('status', 'resolved')->count(),
            'flags' => $flags,
        ];

        return $this->success($response, 'Question flags retrieved successfully');
    }
}
