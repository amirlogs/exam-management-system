<?php

namespace App\Http\Controllers;

use App\Commit\ExamQuestionCommitter;
use App\Http\Requests\AddQuestionsRequest;
use App\Http\Requests\ExamCompositionRequest;
use App\Http\Requests\StoreOnlineExamRequest;
use App\Http\Requests\StoreQuestionRequest;
use App\Jobs\ImportCsv;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ImportHistory;
use App\Models\Question;
use App\Services\ImportCommitterFactory;
use App\Services\ImportValidatorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnlineExamController extends Controller
{
    public function store(CourseOffering $courseOffering, StoreOnlineExamRequest $request)
    {
        $validated = $request->validated();

        if ($courseOffering->status !== 'approved') {
            return $this->error(null, 'Exams can only be created against approved course offerings', 409);
        }

        foreach ($request->composition as $type => $config) {
            $needsMarksEach = in_array($type, ['MCQ', 'TRUE_FALSE']);
            if ($needsMarksEach && empty($config['marks_each'])) {
                return $this->error(null, "marks_each is required for {$type} in the composition.", 422);
            }
        }

        $exam = Exam::create([
            ...$validated,
            'status' => 'draft',
            'created_by' => $request->user()->id,
            'course_offering_id' => $courseOffering->id,
        ]);

        return $this->success($exam->refresh(), 'Exam created successfully', 201);
    }

    public function composition(Exam $exam, ExamCompositionRequest $request)
    {
        $validated = $request->validated();
        if ($exam->status !== 'draft') {
            return $this->error(null, 'Exam is not in draft state', 422);
        }
        $exam->update(['composition' => $validated['composition']]);

        return $this->success($exam->refresh(), 'Exam composition updated successfully');
    }

    public function addQuestions(Exam $exam, AddQuestionsRequest $request)
    {
        $validated = $request->validated();

        if ($exam->status !== 'draft') {
            return $this->error(null, 'Exam is not in draft state', 422);
        }

        $question = Question::find($validated['question_id']);
        $typeConfig = $exam->composition[$question->type] ?? null;

        if (in_array($question->type, ['MCQ', 'TRUE_FALSE'])) {
            $marks = $typeConfig['marks_each'];
        } else {
            if (! $request->has('marks')) {
                return $this->error(null, "marks is required when adding a {$question->type} question.", 422);
            }
            $marks = $request->marks;
        }

        $question = $exam->examQuestions()->create([
            ...$validated,
            'marks' => $marks,
        ]);

        return $this->success($question, 'Question added successfully', 201);
    }

    public function removeQuestion(Exam $exam, ExamQuestion $examQuestion)
    {
        if ($exam->status !== 'draft') {
            return $this->error(null, 'Exam must be in draft state to remove questions', 422);
        }

        $examQuestion->delete();

        return $this->success(null, 'Question removed successfully');
    }

    public function questions(Exam $exam)
    {
        $questions = $exam->questions()->get();

        $totalMarks = $questions->sum(function ($question) {
            return $question->pivot->marks;
        });

        return $this->success([
            'questions' => $questions,
            'total' => $questions->count(),
            'total_points' => $totalMarks,
        ],
            'Questions retrieved successfully');
    }

    public function submitForApproval(Exam $exam)
    {
        if (! $exam->questions()->count()) {
            return $this->error(null, 'Exam must have at least one question to be submitted for approval', 422);
        }

        if ($exam->status !== 'draft') {
            return $this->error(null, 'Exam must be in draft or rejected state to be submitted for approval', 422);
        }

        $exam->update(['status' => 'pending_approval']);

        return $this->success($exam->fresh(), 'Exam submitted for approval successfully');
    }

    public function chnageToDraft(Exam $exam)
    {
        if ($exam->status !== 'pending_approval') {
            return $this->error(null, 'Exam must be in pending approval state to be changed to draft', 422);
        }

        $exam->update(['status' => 'draft']);

        return $this->success($exam->fresh(), 'Exam changed to draft successfully');
    }

    public function approve(Exam $exam, Request $request)
    {
        if ($exam->status !== 'pending_approval') {
            return $this->error(null, 'Exam must be in pending approval state to be approved', 422);
        }

        DB::transaction(function () use ($exam, $request) {
            $exam->update(['status' => 'approved']);
            $exam->approvals()->create([
                'approved_by' => $request->user()->id,
                'status' => 'approved',
                'approved_at' => now(),
            ]);
        });

        return $this->success($exam->fresh(), 'Exam approved successfully');
    }

    public function reject(Exam $exam, Request $request)
    {
        if ($exam->status !== 'pending_approval') {
            return $this->error(null, 'Exam must be in pending approval state to be rejected', 422);
        }

        $request->validate([
            'reason' => 'required|string|min:10|max:255',
        ]);

        DB::transaction(function () use ($exam, $request) {
            $exam->update(['status' => 'draft']);
            $exam->approvals()->create([
                'approved_by' => $request->user()->id,
                'status' => 'rejected',
                'reason' => $request->input('reason'),
                'approved_at' => now(),
            ]);
        });

        return $this->success($exam->fresh(), 'Exam rejected successfully');
    }
}

/*
public function importQuestions(StoreQuestionRequest $request)
    {
        $validated = $request->validated();
        $context = json_decode($validated['context'], true);
        if (! Course::find($context['course_id'])) {
            return $this->error(null, 'Course not found', 404);
        }

        $path = $request->file('file')->store('imports');

        $import = ImportHistory::create([
            'uploaded_by' => $request->user()->id,
            'type' => 'questions',
            'file_path' => $path,
            'context' => ['course_id' => $context['course_id'], 'uploaded_by' => $request->user()->id],
            'status' => 'pending',
        ]);

        // $import->update(['context' => array_merge($import->context, ['import_history_id' => $import->id])]);

        ImportCsv::dispatch($import);

        return $this->success($import, 'Question import queued successfully', 201);

    }

*/

/**
public function confirmImportQuestions(Exam $exam, ImportHistory $importHistory)
{
    $validatorClass = ImportValidatorFactory::create($importHistory->type);
    foreach ($importHistory->validated_data as $row) {
        $errors = $validatorClass::validate($row['data'], $importHistory->context);
        if ($errors) {
            return $this->error($errors, 'All rows must be valid before confirming', 422);
        }
    }

    $committerClass = ImportCommitterFactory::create($importHistory->type);

    DB::transaction(function () use ($importHistory, $committerClass, $exam) {
        foreach ($importHistory->validated_data as $row) {
            $committerClass::commit($row['data'], $importHistory->context);
        }

        $importHistory->update(['status' => 'confirmed']);

        // commit the exam question
        $questions = Question::where('import_history_id', $importHistory->id);
        foreach ($questions as $question) {
            ExamQuestionCommitter::commit($question, $exam);
        }
    });

    return $this->success($importHistory->fresh(), 'Import confirmed successfully');
}

 **/
