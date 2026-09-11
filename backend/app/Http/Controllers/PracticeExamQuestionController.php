<?php

namespace App\Http\Controllers;

use App\Commit\PracticeQuestionCommitter;
use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreQuestionGeneratorRequest;
use App\Http\Search\RequestSearch;
use App\Jobs\GeneratePracticeQuestions;
use App\Models\PracticeExam;
use App\Models\PracticeQuestionHistory;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PracticeExamQuestionController extends Controller
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
            'uploaded_by' => $request->user()->id,
            'pracice_exam_id' => $practiceExam->id,
            'status' => 'pending',
        ]);

        GeneratePracticeQuestions::dispatch($history);

        return $this->success(new PracticeExamQuestionController($history->refresh()), "Generating question for the practice exam ");
    }

    public function show(PracticeQuestionHistory $practiceQuestionHistory)
    {
        return $this->success(new PracticeExamQuestionController($practiceQuestionHistory), "Questions generated successfully");
    }

    public function index(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = PracticeQuestionHistory::query();
        RequestSearch::apply($query, $request, ['input']);
        RequestFilters::apply($query, $request, ['status']);

        $practice_questions = $query->paginate($per_page);

        return $this->paginate($practice_questions, PracticeExamQuestionController::class, 'Questions retrieved successfully');
    }

    public function confirm(PracticeQuestionHistory $practiceQuestionHistory)
    {
        if ($practiceQuestionHistory->status !== 'ready_for_review') {
            return $this->error(null, "the question is not ready for review", 422);
        }

        // return $practiceQuestionHistory->validated_question;
        // map to quesion and link
        DB::transaction(function () use ($practiceQuestionHistory) {
            foreach ($practiceQuestionHistory->validated_question ?? [] as $row) {
                PracticeQuestionCommitter::commit($row['data'], $practiceQuestionHistory);
            }

            $importHistory->update([
                'status' => 'confirmed',
            ]);

            if ($exam) {
                $questions = Question::where('import_history_id', $importHistory->id)->get();
                foreach ($questions as $question) {
                    ExamQuestionCommitter::commit($question, $exam);
                }
                $examQuestions = ExamQuestion::where('exam_id', $exam->id)->get();
                $exam->update([
                    'total_marks' => $examQuestions->sum('marks'),
                    'total_questions' => $examQuestions->count(),
                ]);
            }
        });
        // update the status
    }
}
