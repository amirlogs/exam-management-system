<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlagQuestionRequest;
use App\Models\Question;
use App\Models\QuestionFlag;
use App\Validation\ValidateQuestionRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FlagQuestionController extends Controller
{
    public function store(FlagQuestionRequest $request, int $questionId)
    {
        $message = $request->validated();

        $question = Question::find($questionId);

        if (! $question) {
            $this->error('', 'Question Not Found', 404);
        }
        if ($question->status !== 'confirmed') {
            $this->error('', 'Question Should Be Confirmed First', 404);
        }

        $flag = QuestionFlag::create([
            'question_id' => $questionId,
            'instructor_id' => $request->user()->id,
            'comment' => $message['comment'],
            'status' => 'open',
        ]);

        return $this->success($flag, 'Question Flagged Successfully');
    }

    public function update(Request $request, int $questionId, int $flagId)
    {

        $validatederror = ValidateQuestionRow::validate($request->all());

        if (! empty($validatederror)) {
            return $this->error($validatederror, 'Validation error', 422);
        }
        $question = Question::find($questionId);
        if ($question->status !== 'confirmed') {
            return $this->error('', 'Question Should Be Confirmed First', 404);
        }
        DB::transaction(function () use ($question, $flagId) {
            $question->update([
                'text' => request()->text,
                'options' => request()->options,
                'type' => request()->type,
                'correct_answer' => request()->correct_answer,
                'difficulty' => request()->difficulty,
                'points' => request()->points,
            ]);

            // check flag
            $flag = QuestionFlag::where('question_id', $question->id)->where('id', $flagId)->where('status', 'open')->first();
            if ($flag) {
                $flag->update([
                    'status' => 'resolved',
                    'resolved_by' => request()->user()->id,
                    'resolved_at' => now(),
                ]);
            }
        });

        return $this->success($question, 'Question Updated Successfully');
    }

    public function index(int $importId)
    {
        $import = Question::where('import_id', $importId)->get();

        if (! $import) {
            $this->error('Import not found', 404);
        }
        // does the question exist

        // get the flaged one with that  id
    }

    public function resolve(int $flagId)
    {
        $flag = QuestionFlag::find($flagId);
        if (! $flag) {
            $this->error('Flag not found', 404);
        }
        if ($flag->status !== 'open') {
            $this->error('Flag already resolved', 422);
        }
        $flag->update([
            'status' => 'resolved',
            'resolved_by' => request()->user()->id,
            'resolved_at' => now(),
        ]);

        return $this->success($flag, 'Flag Resolved Successfully');
    }
}
