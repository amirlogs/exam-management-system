<?php

namespace App\Http\Controllers;

use App\Commit\GeneratedQuestionCommitter;
use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreQuestionGeneratorRequest;
use App\Http\Resources\GeneratedQuestionsHistoryResource;
use App\Jobs\GenerateQuestions;
use App\Models\GeneratedQuestionsHistory;
use App\Models\Question;
use App\Validation\GetRequestsValidator;
use App\Validation\ValidateQuestionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function store(StoreQuestionGeneratorRequest $request)
    {
        $validated = $request->validatedInput();

        // Validate optional context
        $contextErrors = ValidateQuestionContext::validate($validated['context']);
        if (!empty($contextErrors)) {
            return $this->error($contextErrors, 'Invalid context for question generation', 422);
        }

        $history = GeneratedQuestionsHistory::create([
            'input' => $validated['input'],
            'count' => $validated['count'],
            'isNote' => $validated['isNote'],
            'type' => $validated['type'],
            'difficulty'  => $validated['difficulty'],
            'uploaded_by' => $request->user()->id,
            'context' => $validated['context'],
            'status'      => 'pending',
        ]);

        GenerateQuestions::dispatch($history);

        return $this->success(new GeneratedQuestionsHistoryResource($history), 'Question generation job dispatched successfully.');
    }


    public function index(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = GeneratedQuestionsHistory::query();
        RequestFilters::apply($query, $request, ['type', 'status']);
        $history = $query->with('user')->latest()->paginate($per_page);


        return $this->paginate($history, GeneratedQuestionsHistoryResource::class, 'Question generation history retrieved successfully');
    }

    public function show(GeneratedQuestionsHistory $generatedQuestionsHistory, Request $request)
    {
        return $this->success(new GeneratedQuestionsHistoryResource($generatedQuestionsHistory->load('user')), 'Question generation history retrieved successfully');
    }

    public function cancel(GeneratedQuestionsHistory $generatedQuestionsHistory)
    {
        if ($generatedQuestionsHistory->status === 'confirmed') {
            return $this->error([], 'Cannot cancel an import that has already been confirmed', 400);
        }

        $generatedQuestionsHistory->delete();
        return $this->success(null, 'Import cancelled successfully');
    }


    public function confirm(GeneratedQuestionsHistory $generatedQuestionsHistory, Request $request)
    {
        if ($generatedQuestionsHistory->status !== 'ready_for_review') {
            return $this->error(null, "Only records in 'ready_for_review' status can be confirmed", 422);
        }

        if (empty($generatedQuestionsHistory->validated_question)) {
            return $this->error(null, 'No questions found to confirm and save', 422);
        }

        GeneratedQuestionCommitter::commitBatch($generatedQuestionsHistory);

        return $this->success(new GeneratedQuestionsHistoryResource($generatedQuestionsHistory->fresh(['user'])), 'Questions confirmed and saved successfully');
    }
}
