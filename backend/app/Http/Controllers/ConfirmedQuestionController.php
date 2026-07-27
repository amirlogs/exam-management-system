<?php

namespace App\Http\Controllers;

use App\Models\Question;

class ConfirmedQuestionController extends Controller
{
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
