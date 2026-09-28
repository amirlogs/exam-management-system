<?php

namespace App\Http\Controllers;

use App\Commit\PracticeExamQuestionCommitter;
use App\Commit\PracticeQuestionCommitter;
use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreQuestionGeneratorRequest;
use App\Http\Resources\PracticeExamHistoryResource;
use App\Http\Resources\PracticeExamQuestionResource;
use App\Http\Search\RequestSearch;
use App\Jobs\GeneratePracticeQuestions;
use App\Models\PracticeExam;
use App\Models\PracticeQuestion;
use App\Models\PracticeQuestionHistory;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PracticeExamHistoryController extends Controller
{
    public function generate(StoreQuestionGeneratorRequest $request, PracticeExam $practiceExam)
    {
        $validated = $request->validated();

        $history = PracticeQuestionHistory::create([
            'input' => $validated['input'],
            'count' => $validated['count'],
            'isNote' => $validated['is_note'],
            'type' => $validated['type'],
            'difficulty'  => $validated['difficulty'],
            'owned_by' => $request->user()->id,
            'practice_exam_id' => $practiceExam->id,
            'status' => 'pending',
        ]);

        GeneratePracticeQuestions::dispatch($history);

        return $this->success(new PracticeExamHistoryResource($history->refresh()), "Generating question for the practice exam ");
    }

    public function show(PracticeQuestionHistory $practiceQuestionHistory)
    {
        return $this->success(new PracticeExamHistoryResource($practiceQuestionHistory), "Questions fetched successfully");
    }

    public function confirm(PracticeQuestionHistory $practiceQuestionHistory)
    {
        if ($practiceQuestionHistory->status !== 'ready_for_review') {
            return $this->error(null, "the question is not ready for review", 422);
        }

        $examId = $practiceQuestionHistory->practice_exam_id;
        $practiceExam = PracticeExam::find($examId);

        // return $practiceQuestionHistory;
        // return $practiceQuestionHistory->validated_question;
        DB::transaction(function () use ($examId, $practiceExam, $practiceQuestionHistory) {
            foreach ($practiceQuestionHistory->validated_question ?? [] as $row) {
                PracticeQuestionCommitter::commit($row, $practiceQuestionHistory);
            }
            $practiceQuestionHistory->update([
                'status' => 'confirmed',
            ]);
            $questions = PracticeQuestion::where('practice_question_histories_id', $practiceQuestionHistory->id)->get();
            foreach ($questions as $question) {
                PracticeExamQuestionCommitter::commit($question, $examId);
            }
            $practiceExam->update([
                'total_questions' => $questions->count(),
                'total_marks' => $questions->count(),
            ]);
        });

        return $this->success(new PracticeExamHistoryResource($practiceQuestionHistory->refresh()), 'Questions confirmed successfully');
    }

    public function index(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = PracticeQuestionHistory::query()->where('owned_by', $request->user()->id);
        RequestSearch::apply($query, $request, ['input']);
        RequestFilters::apply($query, $request, ['status']);

        $practice_questions = $query->paginate($per_page);


        return $this->paginate($practice_questions, PracticeExamHistoryResource::class, 'practice exam history retrieved successfully');
    }

    public function destroyQuestion(PracticeQuestionHistory $practiceQuestionHistory, int $index)
    {
        $questions = $practiceQuestionHistory->validated_question ?? [];

        if (! array_key_exists($index, $questions)) {
            return $this->error(null, 'Generated question not found at this index', 404);
        }

        $targetQuestion = $questions[$index] ?? null;

        unset($questions[$index]);
        $questions = array_values($questions);

        $practiceQuestionHistory->update([
            'validated_question' => $questions,
            'total_rows' => count($questions),
            'valid_count' => count($questions),
        ]);

        if ($practiceQuestionHistory->status === 'confirmed' && $targetQuestion && !empty($targetQuestion['content'])) {
            PracticeQuestion::where('practice_question_histories_id', $practiceQuestionHistory->id)
                ->where('content', $targetQuestion['content'])
                ->delete();
        }

        return $this->success(
            new PracticeExamHistoryResource($practiceQuestionHistory->fresh()),
            'Question deleted successfully from batch'
        );
    }
}
