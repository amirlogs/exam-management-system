<?php

namespace App\Http\Controllers;

use App\Commit\ExamQuestionCommitter;
use App\Http\Filters\RequestFilters;
use App\Http\Requests\AddQuestionsRequest;
use App\Http\Requests\ExamCompositionRequest;
use App\Http\Requests\StoreOnlineExamRequest;
use App\Http\Resources\ExamQuestionResource;
use App\Http\Resources\ExamResource;
use App\Http\Resources\QuestionResource;
use App\Models\CourseOffering;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\ImportHistory;
use App\Models\Question;
use App\Services\ImportCommitterFactory;
use App\Services\ImportValidatorFactory;
use App\Validation\GetRequestsValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LDAP\Result;

class ExamController extends Controller
{
    public function index(CourseOffering $courseOffering, Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = Exam::query();
        RequestFilters::apply($query, $request, ['type', 'status']);
        $exam = $query->where('course_offering_id', $courseOffering->id)->paginate($per_page);

        return $this->paginate($exam, ExamResource::class, 'Exam fetched successfully');
    }

    public function show(Exam $exam)
    {
        return $this->success(new ExamResource($exam), 'Exam fetched successfully');
    }

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

        // return $this->success($exam, 'Exam created successfully', 201);
        return $this->success(new ExamResource($exam->refresh()), 'Exam created successfully', 201);
    }

    public function composition(Exam $exam, ExamCompositionRequest $request)
    {
        $validated = $request->validated();
        if ($exam->status !== 'draft') {
            return $this->error(null, 'Exam is not in draft state', 422);
        }
        $exam->update(['composition' => $validated['composition']]);

        return $this->success(new ExamResource($exam->refresh()), 'Exam composition updated successfully');
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

        $question = DB::transaction(function () use ($exam, $validated, $marks) {
            $question = $exam->examQuestions()->create([
                ...$validated,
                'marks' => $marks,
            ])->load('question');

            $questions = $exam->examQuestions()->with('question')->get();
            $totalMarks = $questions->sum('marks');
            $totalQuestions = $questions->count();
            $exam->update([
                'total_marks' => $totalMarks,
                'total_questions' => $totalQuestions,
            ]);
            return $question;
        });
        return $this->success(new ExamQuestionResource($question), 'Question added successfully', 201);
    }

    public function removeQuestion(Exam $exam, ExamQuestion $examQuestion)
    {
        if ($exam->status !== 'draft') {
            return $this->error(null, 'Exam must be in draft state to remove questions', 422);
        }

        $examQuestion->delete();

        return $this->success(null, 'Question removed successfully');
    }

    public function questions(Exam $exam, Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = $exam->questions()->with('options')->getQuery();
        
        RequestFilters::apply($query, $request, ['type', 'status']);

        $questions = $query->paginate($per_page);
        
        return $this->paginate($questions , QuestionResource::class , "Exam Questions retrieved successfully");
    }

    public function submitApproval(Exam $exam)
    {
        if (! $exam->questions()->count()) {
            return $this->error(null, 'Exam must have at least one question to be submitted for approval', 422);
        }

        if ($exam->status !== 'draft') {
            return $this->error(null, 'Exam must be in draft or rejected state to be submitted for approval', 422);
        }

        $exam->update([
            'status' => 'pending_approval',
            'review_cycle' => $exam->review_cycle + 1,
        ]);

        return $this->success(new ExamResource($exam->fresh()), 'Exam submitted for approval successfully');
    }

    public function revertToDraft(Exam $exam)
    {
        if ($exam->status !== 'pending_approval') {
            return $this->error(null, 'Exam must be in pending approval state to be changed to draft', 422);
        }

        $exam->update(['status' => 'draft']);

        return $this->success(new ExamResource($exam->fresh()), 'Exam changed to draft successfully');
    }
    
    public function approve(Exam $exam, Request $request)
    {
        if ($exam->status !== 'pending_approval') {
            return $this->error(null, 'Exam must be in pending approval state to be approved', 422);
        }

        DB::transaction(function () use ($exam, $request) {
            $review = $exam->approvals()->create([
                'cycle' => $exam->review_cycle,
                'reviewed_by' => $request->user()->id,
                'decision' => 'approved',
            ]);
            $exam->update([
                'status' => 'approved',
                'current_review_id' => $review->id,
            ]);
        });

        return $this->success(new ExamResource($exam->fresh()), 'Exam approved successfully');
    }

    public function reject(Exam $exam, Request $request)
    {
        if ($exam->status !== 'pending_approval') {
            return $this->error(null, 'Exam must be in pending approval state to be rejected', 422);
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        DB::transaction(function () use ($exam, $request) {
            $review = $exam->approvals()->create([
                'cycle' => $exam->review_cycle,
                'reviewed_by' => $request->user()->id,
                'decision' => 'rejected',
                'reason' => $request->input('reason'),
            ]);

            $exam->update([
                'status' => 'rejected',
                'current_review_id' => $review->id,
            ]);
        });
        
        return $this->success(new ExamResource($exam->fresh()),'Exam rejected successfully');
    }

    public function schedule(Exam $exam, Request $request)
    {
        if ($exam->status !== 'approved') {
            return $this->error(null, 'Exam must be approved to be scheduled', 422);
        }

        $request->validate([
            'scheduled_start' => 'required|date',
        ]);

        $localDateTime = Carbon::parse($request->scheduled_start_time)->setTimezone('UTC');
        $scheduleEnd = $localDateTime->copy()->addMinutes($exam->duration_minutes);

        $exam->update([
            'status' => 'scheduled',
            'scheduled_start' => $localDateTime,
            'scheduled_end' => $scheduleEnd,
        ]);

        return $this->success(new ExamResource($exam->fresh()), 'Exam scheduled successfully');
    }

    public function updateSchedule(Exam $exam, Request $request)
    {
        if ($exam->status !== 'scheduled') {
            return $this->error(null, 'Exam must be scheduled to be updated', 422);
        }
        $request->validate([
            'scheduled_start' => ['sometimes', 'date'],
            'duration_minutes' => ['sometimes', 'integer', 'min:30'],

        ]);
        if ($request->scheduled_start_time) {
            $localDateTime = Carbon::parse($request->scheduled_start_time)->setTimezone('UTC');
            $scheduleEnd = $localDateTime->copy()->addMinutes($exam->duration_minutes);
        }
        $exam->update([
            'scheduled_start' => $localDateTime ?? $exam->scheduled_start,
            'scheduled_end' => $scheduleEnd ?? $exam->scheduled_end,
            'duration_minutes' => $request->duration_minutes ?? $exam->duration_minutes,
        ]);

        return $this->success(new ExamResource($exam->fresh()), 'Exam schedule updated successfully');
    }

    public function extendTime(Exam $exam, Request $request)
    {
        if ($exam->status !== 'active') {
            return $this->error(null, 'Exam must be ongoing to be extended', 422);
        }
        $request->validate([
            'duration_minutes' => ['required', 'integer', 'min:1'],
        ]);
        $newEndTime = Carbon::parse($exam->scheduled_end)->addMinutes($request->duration_minutes);

        $exam->update([
            'scheduled_end' => $newEndTime,
        ]);

        return $this->success(new ExamResource($exam->fresh()), 'Exam time extended successfully');
    }

    public function publish(Exam $exam)
    {
        if ($exam->status !== 'scheduled') {
            return $this->error(null, 'Exam must be scheduled to be published', 422);
        }

        $exam->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        return $this->success(new ExamResource($exam->fresh()), 'Exam published successfully');
    }

    public function end(Exam $exam)
    {
        if ($exam->status !== 'active') {
            return $this->error(null, 'Exam must be active to be completed', 422);
        }

        DB::transaction(function () use ($exam) {
            ExamAttempt::where('exam_id', $exam->id)->update(['status' => 'auto_submitted']);
            $exam->update([
                'status' => 'completed',
            ]);
        });

        return $this->success(new ExamResource($exam->fresh()), 'Exam completed successfully');
    }

    public function archive(Exam $exam)
    {
        if ($exam->status !== 'closed') {
            return $this->error(null, 'Exam must be closed to be archived', 422);
        }

        $exam->update([
            'status' => 'archived',
        ]);

        return $this->success(new ExamResource($exam->fresh()), 'Exam archived successfully');
    }
}

