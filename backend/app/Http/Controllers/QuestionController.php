<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Resources\QuestionResource;
use App\Http\Search\RequestSearch;
use App\Models\Course;
use App\Models\Question;
use App\Validation\GetRequestsValidator;
use App\Validation\QuestionValidator;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = Question::forUser($request->user());
        RequestFilters::apply($query, $request, ['course_id', 'import_history_id', 'type', 'chapter', 'status', 'difficulty']);
        RequestSearch::apply($query, $request, [ 'content', 'chapter', ]);

        $questions = $query->with('course')->paginate($per_page);

        return $this->paginate($questions, QuestionResource::class, 'Questions fetched successfully');
    }

    public function show(Question $question)
    {
        $this->authorize('view', $question);
        return $this->success(new QuestionResource($question->load('options', 'approvals', 'course')), 'Question fetched successfully');
    }

    public function store(Course $course, Request $request)
    {
        $this->authorize('create', $course);
        $errors = QuestionValidator::validate($request->all());

        if ($errors) {
            return $this->error($errors, 'Validation failed', 422);
        }

        $question = Question::create([
            'course_id' => $course->id,
            'created_by' => $request->user()->id,
            'type' => strtolower($request->type),
            'chapter' => $request->chapter,
            'content' => $request->content,
            'difficulty' => $request->difficulty,
            'status' => 'active',
        ]);

        foreach (QuestionValidator::buildOptions($request->type, $request->options ?? [], $request->correct_answer) as $option) {
            $question->options()->create($option);
        }

        return $this->success(new QuestionResource($question->load('options')), 'Question created successfully', 201);
    }

    public function update(Question $question, Request $request)
    {
        $this->authorize('update', $question);
        $errors = QuestionValidator::validate($request->all());
        if ($errors) {
            return $this->error($errors, 'Validation Error', 422);
        }

        $question->update([
            'type' => strtolower($request->type),
            'chapter' => $request->chapter,
            'content' => $request->content,
            'difficulty' => $request->difficulty,
        ]);

        if (! in_array($request->type, ['mcq', 'true_false'])) {
            $question->options()->delete();
        } elseif ($request->has('options')) {
            $question->options()->delete();

            foreach (
                QuestionValidator::buildOptions(
                    $request->type,
                    $request->options,
                    $request->correct_answer
                ) as $option
            ) {
                $question->options()->create($option);
            }
        }

        return $this->success(new QuestionResource($question), 'Question updated successfully');
    }

    public function destroy(Question $question)
    {
        $this->authorize('archive', $question);
        $question->update(['status' => 'archived']);
        $question->delete();

        return $this->success(null, 'Question archived successfully');
    }

    public function restore(string $id)
    {
        $question = Question::onlyTrashed()->find($id);
        if (! $question) {
            return $this->error(null, 'Question not found in archived questions.', 404);
        }
        $this->authorize('restore', $question);
        $question->restore();
        $question->update(['status' => 'active']);

        return $this->success(new QuestionResource($question), 'Question restored successfully');
    }

    public function archived(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = Question::forUser($request->user());
        RequestFilters::apply($query, $request, ['course_id', 'import_history_id', 'type', 'chapter', 'status', 'difficulty']);
        RequestSearch::apply($query, $request, [ 'content', 'chapter', ]);

        $questions = $query->with('course')->onlyTrashed()->paginate($per_page);
        return $this->paginate($questions, QuestionResource::class, 'Archived questions retrieved successfully');
    }

    public function showArchived(string $id)
    {
        $question = Question::onlyTrashed()
            ->with([
                'course',
                'options',
                'approvals',
            ])
            ->find($id);

        if (! $question) {
            return $this->error('Archived question not found', 404);
        }

        $this->authorize('view', $question);

        return $this->success(
            new QuestionResource($question),
            'Archived question fetched successfully'
        );
    }
}
