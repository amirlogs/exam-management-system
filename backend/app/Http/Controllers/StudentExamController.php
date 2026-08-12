<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExamAttemptResource;
use App\Http\Resources\ExamQuestionResource;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Services\SubmitAnswerFactory;
use Illuminate\Http\Request;

class StudentExamController extends Controller
{
    public function start(Exam $exam, Request $request)
    {
        $this->authorize('viewAsStudent', $exam);

        $student = $request->user()->student;

        if ($exam->status !== 'active') {
            return $this->error(null, 'Exam is not active', 403);
        }
        // if exist send the existing else send the new one
        $existing = ExamAttempt::where('exam_id', $exam->id)->where('student_id', $student->id)->first();
        if ($existing) {
            if ($existing->status !== 'in_progress') {
                return $this->error(null, ' You have already submitted  the exam', 409);
            }

            return $this->success(new ExamAttemptResource($existing), 'Exam fetched successfully');
        }
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $this->success(new ExamAttemptResource($attempt), 'Exam fetched successfully');
    }

    public function show(Exam $exam)
    {
        $this->authorize('viewAsStudent', $exam);
        if (! in_array($exam->status, ['scheduled', 'active', 'completed'])) {
            return $this->error(null, 'Exam not available', 404);
        }

        return $this->success(new ExamResource($exam), 'Exam started successfully');

    }

    public function questions(Exam $exam, Request $request)
    {

        $this->authorize('viewAsStudent', $exam);

        if ($exam->status !== 'active') {
            return $this->error(null, 'Questions are not visible yet — waiting for the exam to be started.', 403);
        }

        $studentId = $request->user()->student->id;
        $questions = $exam->questions()->with('options')->get();
        $attemptsAnswers = $exam->attempts()->where('student_id', $studentId)->first()->answers->keyBy('question_id');

        $data = $questions->map(function ($question) use ($attemptsAnswers) {
            $attemptAnswer = $attemptsAnswers->get($question->id);

            return [
                'id' => $question->id,
                'question' => new ExamQuestionResource($question),
                'type' => $question->type,
                'selected_answer_id' => $attemptAnswer?->selected_option_id,
                'answer_text' => $attemptAnswer?->answer_text,
            ];
        });

        return $this->success($data, 'Exam fetched successfully');

    }
    
    public function answer(ExamAttempt $examAttempt, Request $request)
    {
        // authrorize the user is the student of the exam attempt
        $this->authorize('canSubmitAnswer', $examAttempt);
        $exam = $examAttempt->exam;

        $question = ExamQuestion::find($request->exam_question_id)->question;

        // validation
        $validatorClass = SubmitAnswerFactory::create($question->type);

        $errors = $validatorClass::validate($request->all());
        if ($errors) {
            return $this->error($errors, 'Validation errors', 422);
        }

        // check exam status
        if ($examAttempt->status !== 'in_progress') {
            return $this->error(null, 'Exam attempt is not in progress', 403);
        }
        if ($exam->status !== 'active') {
            $examAttempt->update(['status' => 'completed']);

            return $this->error(null, 'Exam is completed', 403);
        }

        // submit the answere
        $examAttempt->answers()->create(
            [
                'question_id' => $question->id,
                'exam_question_id' => $request->exam_question_id,
                'selected_option_id' => $request->selected_option_id ?? null,
                'answer_text' => $request->answer_text ?? null,
            ]
        );

        return $this->success(null, 'Answer submitted successfully');
    }

    public function submit(ExamAttempt $examAttempt)
    {

        if ($examAttempt->status !== 'in_progress') {
            return $this->error(null, 'Exam attempt is already submitted', 403);
        }
        $examAttempt->update([
            'status' => 'completed',
            'submitted_at' => now(),
        ]);

        return $this->success(null, 'Exam attempt submitted successfully');
    }
}
