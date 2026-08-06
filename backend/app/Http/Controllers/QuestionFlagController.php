<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionFlag;
use Illuminate\Http\Request;

class QuestionFlagController extends Controller
{
    public function index(Question $question)
    {
        $flags = $question->flags()->with('flagger')->latest()->get();

        return $this->success([
            'question_id' => $question->id,
            'open_count' => $flags->where('status', 'open')->count(),
            'resolved_count' => $flags->where('status', 'resolved')->count(),
            'flags' => $flags,
        ], 'Question flags retrieved successfully');
    }

    public function store(Question $question, Request $request)
    {
        $request->validate(['comment' => 'required|string']);

        if ($question->status !== 'approved') {
            return $this->error(null, 'Only approved questions can be flagged', 400);
        }

        $flag = QuestionFlag::create([
            'question_id' => $question->id,
            'flagged_by' => $request->user()->id,
            'comment' => $request->comment,
            'status' => 'open',
        ]);

        return $this->success($flag, 'Question flagged successfully', 201);
    }

    public function resolve(QuestionFlag $flag, Request $request)
    {
        if ($flag->status !== 'open') {
            return $this->error('Flag already resolved', null, 409);
        }

        $flag->update([
            'status' => 'resolved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return $this->success($flag, 'Flag resolved successfully');
    }
}
