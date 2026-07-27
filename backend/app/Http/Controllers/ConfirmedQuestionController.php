<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Validation\ValidateQuestionRow;
use Illuminate\Http\Request;

class ConfirmedQuestionController extends Controller
{
    public function update(Request $request, int $questionId)
    {

        $validatederror = ValidateQuestionRow::validate($request->all());

        if (! empty($validatederror)) {
            return $this->error($validatederror, 'Validation error', 422);
        }
        $question = Question::find($questionId);
        if (! $question->status === 'approved') {
            return $this->error('', 'Question is Approved, You can ask admin for permmision', 404);
        }
        if ($question->status !== 'confirmed') {
            return $this->error('', 'Question Should Be Confirmed First', 404);
        }
        $question->update([
            'text' => request()->text,
            'options' => request()->options,
            'type' => request()->type,
            'correct_answer' => request()->correct_answer,
            'difficulty' => request()->difficulty,
            'points' => request()->points,
        ]);

        return $this->success($question, 'Question Updated Successfully');
    }

    public function destroy(int $questionId)
    {
        $question = Question::find($questionId);
        if (! $question->status === 'approved') {
            return $this->error('', 'Question is Approved, You can ask admin for permmision', 404);
        }
        $question->delete();

        return $this->success('', 'Question Deleted Successfully');
    }

    public function index(int $importId)
    {
        $quesion = Question::where('import_id', $importId)->get();
        if ($quesion->isEmpty()) {
            return $this->error('', 'No Question Found', 404);
        }

        return $this->success($quesion, 'Question Fetched Successfully');
    }

    public function show(int $questionId)
    {
        $quesion = Question::where('id', $questionId)
            ->with('instructor.user:id,name')
            ->orderByRaw("status = 'resolved'")
            ->latest()
            ->get();
        if (! $quesion) {
            return $this->error('', 'No Question Found', 404);
        }

        return $this->success($quesion, 'Question Fetched Successfully');

    }
}
