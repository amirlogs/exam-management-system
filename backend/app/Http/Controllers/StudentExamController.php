<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Resources\ExamAttemptResource;
use App\Http\Resources\ExamQuestionResource;
use App\Http\Resources\StudentExamResource;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Services\SubmitAnswerFactory;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class StudentExamController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student;
        if (! $student) {
            return $this->error(null, 'Student profile not found.', 403);
        }

        $perPage = GetRequestsValidator::validate($request);

        $courseOfferingIds = $student->enrollments()
            ->where('status', 'active')
            ->pluck('course_offering_id');

        $query = Exam::whereIn('course_offering_id', $courseOfferingIds)
            ->whereIn('status', ['scheduled', 'active', 'completed'])
            ->with([
                'courseOffering.course',
                'courseOffering.semester',
                'attempts' => fn ($q) => $q->where('student_id', $student->id),
            ]);

        RequestFilters::apply($query, $request, ['status', 'type']);

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                    ->orWhereHas('courseOffering.course', function ($cq) use ($search) {
                        $cq->where('code', 'ILIKE', "%{$search}%")
                            ->orWhere('name', 'ILIKE', "%{$search}%");
                    });
            });
        }

        $exams = $query->latest()->paginate($perPage);

        return $this->paginate($exams, StudentExamResource::class, 'Student exams fetched successfully');
    }

    public function start(Exam $exam, Request $request)
    {
        $this->authorize('viewAsStudent', $exam);

        $student = $request->user()->student;
        if (! $student) {
            return $this->error(null, 'Student profile not found.', 403);
        }

        if ($exam->status !== 'active') {
            return $this->error(null, 'Exam is not active', 403);
        }

        $existing = ExamAttempt::where('exam_id', $exam->id)->where('student_id', $student->id)->first();
        if ($existing) {
            if ($existing->status !== 'in_progress') {
                return $this->error(null, 'You have already submitted the exam', 409);
            }

            return $this->success(new ExamAttemptResource($existing), 'Exam attempt fetched successfully');
        }

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $this->success(new ExamAttemptResource($attempt), 'Exam started successfully');
    }

    public function show(Exam $exam, Request $request)
    {
        $this->authorize('viewAsStudent', $exam);
        if (! in_array($exam->status, ['scheduled', 'active', 'completed'])) {
            return $this->error(null, 'Exam not available', 404);
        }

        $student = $request->user()->student;
        $exam->load([
            'courseOffering.course',
            'courseOffering.semester',
            'attempts' => fn ($q) => $student ? $q->where('student_id', $student->id) : $q,
        ]);

        return $this->success(new StudentExamResource($exam), 'Exam fetched successfully');
    }

    public function questions(Exam $exam, Request $request)
    {
        $this->authorize('viewAsStudent', $exam);

        if ($exam->status === 'completed') {
            return $this->error(null, 'Exam has already been completed.', 403);
        }

        if ($exam->status !== 'active') {
            return $this->error(null, 'Questions are not visible yet — waiting for the exam to be started.', 403);
        }

        $student = $request->user()->student;
        $studentId = $student?->id;

        $examQuestions = $exam->examQuestions()
            ->with(['question.options'])
            ->orderBy('order_number')
            ->get();

        $attempt = $studentId
            ? $exam->attempts()->where('student_id', $studentId)->first()
            : null;

        $attemptAnswers = $attempt
            ? $attempt->answers->keyBy('exam_question_id')
            : collect();

        $data = $examQuestions->map(function ($eq) use ($attemptAnswers) {
            $question = $eq->question;
            $attemptAnswer = $attemptAnswers->get($eq->id);

            return [
                'id' => $eq->id,
                'exam_question_id' => $eq->id,
                'question_id' => $question?->id,
                'order_number' => $eq->order_number,
                'marks' => $eq->marks,
                'type' => $question?->type,
                'content' => $question?->content,
                'options' => $question?->options->map(fn ($o) => [
                    'id' => $o->id,
                    'option_text' => $o->option_text,
                ]),
                'selected_answer_id' => $attemptAnswer?->selected_option_id,
                'answer_text' => $attemptAnswer?->answer_text,
            ];
        });

        return $this->success($data, 'Questions fetched successfully');
    }

    public function saveAnswer(ExamAttempt $attempt, Request $request)
    {
        $this->authorize('canSubmitAnswer', $attempt);
        $exam = $attempt->exam;

        $examQuestion = ExamQuestion::findOrFail($request->exam_question_id);
        $question = $examQuestion->question;

        // validation
        $validatorClass = SubmitAnswerFactory::create($question->type);

        $errors = $validatorClass::validate($request->all());
        if ($errors) {
            return $this->error($errors, 'Validation errors', 422);
        }

        // check exam status
        if ($attempt->status !== 'in_progress') {
            return $this->error(null, 'Exam attempt is not in progress', 403);
        }
        if ($exam->status !== 'active') {
            $attempt->update(['status' => 'completed']);

            return $this->error(null, 'Exam is completed', 403);
        }

        // submit / update the answer
        $attempt->answers()->updateOrCreate(
            [
                'exam_question_id' => $examQuestion->id,
            ],
            [
                'question_id' => $question->id,
                'selected_option_id' => $request->selected_option_id ?? null,
                'answer_text' => $request->answer_text ?? null,
            ]
        );

        return $this->success(null, 'Answer submitted successfully');
    }

    public function submit(ExamAttempt $attempt)
    {
        $this->authorize('canSubmitAnswer', $attempt);

        if ($attempt->status === 'completed' || $attempt->status === 'auto_submitted') {
            return $this->success(null, 'Exam attempt has already been submitted');
        }

        if ($attempt->status !== 'in_progress') {
            return $this->error(null, 'Exam attempt is already submitted', 403);
        }

        $attempt->update([
            'status' => 'completed',
            'submitted_at' => now(),
        ]);

        return $this->success(null, 'Exam attempt submitted successfully');
    }
}
