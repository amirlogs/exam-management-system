<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\Course;
use App\Models\Question;
use App\Validation\QuestionValidator;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $questions = Question::when($request->course_id, fn ($q, $id) => $q->where('course_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->chapter, fn ($q, $c) => $q->where('chapter', $c))
            ->get();

        return $this->success(QuestionResource::collection($questions), 'Questions fetched successfully');
    }

    public function show(Question $question)
    {
        return $this->success(new QuestionResource($question->load('options', 'approvals')), 'Question fetched successfully');
    }

    public function store(int $courseId, Request $request)
    {
        if (! Course::find($courseId)) {
            return $this->error(null, 'Course not found', 404);
        }

        $errors = QuestionValidator::validate($request->all());

        if ($errors) {
            return $this->error($errors, 'Validation failed', 422);
        }

        $question = Question::create([
            'course_id' => $courseId,
            'created_by' => $request->user()->id,
            'type' => $request->type,
            'chapter' => $request->chapter,
            'content' => $request->content,
            'difficulty' => $request->difficulty,
            'status' => 'draft',
        ]);

        if (in_array($request->type, ['MCQ', 'TRUE_FALSE']) && $request->options) {
            $correctAnswer = strtolower(trim((string) $request->correct_answer));
            foreach ($request->options as $optionText) {
                $question->options()->create([
                    'option_text' => $optionText,
                    'is_correct' => strtolower(trim((string) $optionText)) === $correctAnswer,
                ]);
            }
        }

        return $this->success(new QuestionResource($question->load('options')), 'Question created successfully', 201);
    }

    public function update(Question $question, Request $request)
    {
        if (in_array($question->status, ['approved', 'archived'])) {
            return $this->error('Approved or archived questions cannot be edited directly — ask an admin.', null, 409);
        }

        $errors = QuestionValidator::validate($request->all());
        if ($errors) {
            return $this->error('Validation error', $errors, 422);
        }

        $question->update([
            'type' => $request->type,
            'chapter' => $request->chapter,
            'content' => $request->content,
            'difficulty' => $request->difficulty,
        ]);

        if (in_array($request->type, ['MCQ', 'TRUE_FALSE']) && $request->options) {
            $question->options()->delete(); // replace wholesale — simpler than diffing

            $correctAnswer = strtolower(trim((string) $request->correct_answer));
            foreach ($request->options as $optionText) {
                $question->options()->create([
                    'option_text' => $optionText,
                    'is_correct' => strtolower(trim((string) $optionText)) === $correctAnswer,
                ]);
            }
        }

        return $this->success(new QuestionResource($question->fresh('options')), 'Question updated successfully');
    }

    public function submitApproval(Question $question)
    {
        if ($question->status !== 'draft') {
            return $this->error('Only draft questions can be submitted for approval', null, 409);
        }

        $question->update(['status' => 'pending_approval']);

        return $this->success(new QuestionResource($question), 'Question submitted for approval successfully');
    }

    public function approve(Question $question, Request $request)
    {
        if ($question->status !== 'pending_approval') {
            return $this->error('Only questions pending approval can be approved', null, 409);
        }

        $question->update(['status' => 'approved']);
        $question->approvals()->create([
            'approved_by' => $request->user()->id,
            'status' => 'approved',
            'comment' => $request->comment,
            'approved_at' => now(),
        ]);

        return $this->success(new QuestionResource($question), 'Question approved successfully');
    }

    public function reject(Question $question, Request $request)
    {
        $request->validate(['comment' => 'required|string']);

        if ($question->status !== 'pending_approval') {
            return $this->error('Only questions pending approval can be rejected', null, 409);
        }

        $question->update(['status' => 'rejected']);
        $question->approvals()->create([
            'approved_by' => $request->user()->id,
            'status' => 'rejected',
            'comment' => $request->comment,
            'approved_at' => now(),
        ]);

        return $this->success(new QuestionResource($question), 'Question rejected successfully');
    }

    public function destroy(Question $question)
    {
        if ($question->status === 'approved') {
            return $this->error('Approved questions cannot be deleted — archive instead if no longer needed.', null, 409);
        }

        $question->delete();

        return $this->success(null, 'Question archived successfully');
    }
}
