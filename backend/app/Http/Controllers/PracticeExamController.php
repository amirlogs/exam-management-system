<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Requests\StorePracticeExamRequest;
use App\Http\Requests\UpdatePracticeExamRequest;
use App\Http\Resources\PracticeExamResource;
use App\Http\Search\RequestSearch;
use App\Models\PracticeAnswers;
use App\Models\PracticeExam;
use App\Models\QuestionGuidances;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class PracticeExamController extends Controller
{
    public function store(StorePracticeExamRequest $request)
    {
        $validated = $request->validated();

        foreach ($request->composition as $type => $config) {
            $needsMarksEach = in_array($type, ['MCQ', 'TRUE_FALSE']);
            if ($needsMarksEach && empty($config['marks_each'])) {
                return $this->error(null, "marks_each is required for {$type} in the composition.", 422);
            }
        }
        $practice_exam = PracticeExam::create([
            ...$validated,
            'owned_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        return $this->success(new PracticeExamResource($practice_exam->refresh()), "Practice Exam Created Sucessfully");
    }

    public function index(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = PracticeExam::query()->where('owned_by', $request->user()->id);
        RequestSearch::apply($query, $request, ['title']);
        RequestFilters::apply($query, $request, ['status']);
        $practice_exams = $query->paginate($per_page);

        return $this->paginate($practice_exams, PracticeExamResource::class, 'Practice Exams retrieved successfully');
    }

    public function show(PracticeExam $practiceExam)
    {
        return $this->success(new PracticeExamResource($practiceExam), 'Practice Exam retrieved successfully');
    }

    public function update(UpdatePracticeExamRequest $request, PracticeExam $practiceExam)
    {
        $validated = $request->validated();
        $practiceExam->update([
            ...$validated,
        ]);

        return $this->success(new PracticeExamResource($practiceExam->refresh()), 'Practice Exam updated successfully');
    }

    public function start(PracticeExam $practiceExam)
    {
        if ($practiceExam->status !== 'draft') {
            return $this->error(null, 'Practice Exam is not in draft state', 422);
        }
        $practiceExam->update(['status' => 'active']);

        return $this->success(new PracticeExamResource($practiceExam->refresh()), 'Practice Exam started successfully');
    }

    public function complete(PracticeExam $practiceExam)
    {
        if ($practiceExam->status !== 'active') {
            return $this->error(null, 'Practice Exam is not in active state', 422);
        }
        $practiceExam->update(['status' => 'completed']);

        return $this->success(new PracticeExamResource($practiceExam->refresh()), 'Practice Exam completed successfully');
    }

    public function retake(PracticeExam $practiceExam, Request $request)
    {
        PracticeAnswers::where('practice_exam_id', $practiceExam->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        QuestionGuidances::where('practice_exam_id', $practiceExam->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        $practiceExam->update(['status' => 'active']);

        return $this->success(new PracticeExamResource($practiceExam->refresh()), 'Practice Exam reset for retake successfully');
    }

    public function destroy(PracticeExam $practiceExam)
    {
        $practiceExam->delete();

        return $this->success(null, 'Practice Exam deleted successfully');
    }
}
