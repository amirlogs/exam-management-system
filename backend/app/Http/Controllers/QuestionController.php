<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuestionResource;
use App\Models\Course;
use App\Models\Question;
use App\Validation\ValidateQuestionRow;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index() {}

    public function store(Request $request, string $courseId)
    {
        $course = Course::find($courseId);

        if (! $course) {
            return $this->error('', 'Course not found', 404);
        }
        $validatederror = ValidateQuestionRow::validate($request->all());

        if (! empty($validatederror)) {
            return $this->error($validatederror, 'Validation error', 422);
        }

        $question = Question::create([
            'course_id' => $courseId,
            'text' => $request->text,
            'options' => $request->options ?? null,
            'type' => $request->type,
            'correct_answer' => $request->correct_answer,
            'difficulty' => $request->difficulty,
            'points' => $request->points,
            'status' => 'draft',
            'is_active' => false,
        ]);

        return $this->success($question, 'Question created successfully');
    }

    public function update(string $questionId, Request $request)
    {
        $question = Question::find($questionId);
        if (! $question) {
            return $this->error('', 'Question not found', 404);
        }
        if ($question->status == 'confirmed') {
            return $this->error('', 'Question already confirmed', 422);
        }
        $validatederror = ValidateQuestionRow::validate($request->all());

        if (! empty($validatederror)) {
            return $this->error($validatederror, 'Validation error', 422);
        }

        // $question = Question::where('id', $questionId)->update([
        //     'course_id' => $question->course_id,
        //     'text' => $request->text,
        //     'options' => $request->options ?? null,
        //     'type' => $request->type,
        //     'correct_answer' => $request->correct_answer,
        //     'difficulty' => $request->difficulty,
        //     'points' => $request->points,
        //     'status' => 'draft',
        //     'is_active' => false,
        // ])->fresh();

        $question->update([
            'course_id' => $question->course_id,
            'text' => $request->text,
            'options' => $request->options ?? null,
            'type' => $request->type,
            'correct_answer' => $request->correct_answer,
            'difficulty' => $request->difficulty,
            'points' => $request->points,
            'status' => 'draft',
            'is_active' => false,
        ]);

        return $this->success(new QuestionResource($question), 'Question confirmed successfully');
    }

    public function confirm(Request $request, string $questionId)
    {
        $question = Question::find($questionId);
        if (! $question) {
            return $this->error('', 'Question not found', 404);
        }
        if ($question->status == 'confirmed') {
            return $this->error('', 'Question already confirmed', 422);
        }

        $question->update([
            'status' => 'confirmed',
            'is_active' => true,
        ]);

        return $this->success($question, 'Question confirmed successfully');
    }
}
