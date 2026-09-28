<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Requests\StorePracticeQuestionAnswerRequest;
use App\Http\Resources\PracticeQuestionResource;
use App\Http\Resources\QuestionGuidanceResource;
use App\Http\Search\RequestSearch;
use App\Jobs\QuestionGuidanceJob;
use App\Models\PracticeAnswers;
use App\Models\PracticeExam;
use App\Models\PracticeQuestion;
use App\Models\PracticeQuestionOption;
use App\Models\QuestionGuidances;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PracticeExamQuestionController extends Controller
{
    public function index(Request $request, PracticeExam $practiceExam)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = PracticeQuestion::query();
        RequestSearch::apply($query, $request, ['content']);
        RequestFilters::apply($query, $request, ['status', 'type', 'difficulty']);

        if ($practiceExam->status === 'draft' || $practiceExam->status === 'active') {
            $query->with('options');
        } elseif ($practiceExam->status === 'completed') {
            $query->with('optionsWithoutAnswers');
        }

        $practice_questions = $query->where('practice_exam_id', $practiceExam->id)->paginate($per_page);



        return $this->paginate($practice_questions, PracticeQuestionResource::class, 'practice exam questions retrieved successfully');
    }

    public function store(StorePracticeQuestionAnswerRequest $request, PracticeExam $practiceExam, PracticeQuestion $practiceQuestion)
    {
        if ($practiceExam->status !== 'active') {
            $practiceExam->update(['status' => 'active']);
        }

        $validated = $request->validated();
        $option = PracticeQuestionOption::where('practice_question_id', $practiceQuestion->id)
            ->findOrFail($validated['option_id']);

        $answer = DB::transaction(function () use ($option, $practiceExam, $practiceQuestion, $validated, $request) {
            return PracticeAnswers::updateOrCreate(
                [
                    'practice_exam_id' => $practiceExam->id,
                    'practice_question_id' => $practiceQuestion->id,
                    'user_id' => $request->user()->id,
                ],
                [
                    'selected_option_id' => $validated['option_id'],
                    'is_correct' => $option->is_correct,
                ]
            );
        });

        $correctOption = $practiceQuestion->options()->where('is_correct', true)->first();

        return $this->success([
            'answer' => $answer,
            'is_correct' => (bool) $option->is_correct,
            'selected_option_id' => $option->id,
            'correct_option_id' => $correctOption?->id,
        ], 'Answer submitted successfully');
    }

    public function destroy(PracticeExam $practiceExam, PracticeQuestion $practiceQuestion)
    {
        $practiceQuestion->delete();
        $practiceExam->decrement('total_questions');
        $practiceExam->decrement('total_marks');

        return $this->success(null, 'Practice question deleted successfully');
    }

    public function storeGuidance(PracticeExam $practiceExam, PracticeQuestion $practiceQuestion, Request $request)
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error(null, 'Student profile not found.', 403);
        }
        $request->validate([ 'prompt' => ['required', 'string'], ]);

        $guidance = QuestionGuidances::create([
            'user_id' => $request->user()->id,
            'student_id' => $student->id,
            'practice_exam_id' => $practiceExam->id,
            'practice_question_id' => $practiceQuestion->id,
            'prompt' => $request->input('prompt'),
        ]);

        //dispactch a new job
        QuestionGuidanceJob::dispatch($guidance);

        return $this->success(new QuestionGuidanceResource($guidance), 'Guidance is generating successfully');
    }

    public function getGuidance(PracticeExam $practiceExam, PracticeQuestion $practiceQuestion, Request $request)
    {
        $guidance = QuestionGuidances::where('practice_exam_id', $practiceExam->id)
            ->where('practice_question_id', $practiceQuestion->id)
            ->where('user_id', $request->user()->id)
            ->get();



        return $this->success(QuestionGuidanceResource::collection($guidance), 'Guidance is generating successfully');
    }
}
