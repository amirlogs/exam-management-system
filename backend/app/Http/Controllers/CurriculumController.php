<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreAddCourseRequest;
use App\Http\Requests\StoreCurriculumRequest;
use App\Http\Requests\UpdateAddCourseRequest;
use App\Http\Requests\UpdateCurriculumRequest;
use App\Http\Resources\CurriculumCourseResource;
use App\Http\Resources\CurriculumResource;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class CurriculumController extends Controller
{
    public function store(StoreCurriculumRequest $request)
    {
        $validated = $request->validated();
        $curriculum = Curriculum::create($validated);

        return $this->success(new CurriculumResource($curriculum), 'Curriculum created successfully', 201);
    }

    public function index(Request $request)
    {
        $perPage = GetRequestsValidator::validate($request);
        $query = Curriculum::query();
        RequestFilters::apply($query, $request, ['program_id']);
        $curriculums = $query->with('courses')->paginate($perPage);

        return $this->paginate($curriculums, CurriculumResource::class, 'Curriculums retrieved successfully', 200);
    }

    public function update(Curriculum $curriculum, UpdateCurriculumRequest $request)
    {
        $validated = $request->validated();
        $curriculum->update($validated);

        return $this->success(new CurriculumResource($curriculum), 'Curriculum updated successfully', 200);
    }

    public function addCourse(StoreAddCourseRequest $request, Curriculum $curriculum)
    {
        $validated = $request->validated();
        if ($curriculum->curriculumCourses()->where('course_id', $validated['course_id'])->exists()) {
            return $this->error(null, 'Course already exists in curriculum', 400);
        }
        $curriculumCourse = $curriculum->curriculumCourses()->create($validated);

        return $this->success(new CurriculumCourseResource($curriculumCourse->load('course')), 'Course added to curriculum successfully', 201);
    }

    public function updateCourse(CurriculumCourse $curriculumCourse, UpdateAddCourseRequest $request)
    {
        $validated = $request->validated();
        $curriculumCourse->update($validated);

        return $this->success(new CurriculumCourseResource($curriculumCourse->load('course')), 'Course updated successfully', 200);
    }

    public function removeCourse(CurriculumCourse $curriculumCourse)
    {
        $curriculumCourse->delete();

        return $this->success(null, 'Course removed from curriculum successfully', 200);
    }

    public function activate(Curriculum $curriculum)
    {
        if ($curriculum->isActive()) {
            return $this->error(null, 'Curriculum is already active', 400);
        }

        if ($curriculum->program->curriculums()->where('status', 'active')->exists()) {
            return $this->error(null, 'There is already an active curriculum in this program', 400);
        }

        $curriculum->update(['status' => 'active']);

        return $this->success(new CurriculumResource($curriculum->load('courses')), 'Curriculum activated successfully', 200);
    }

    public function deactivate(Curriculum $curriculum)
    {
        if (! $curriculum->isActive()) {
            return $this->error(null, 'Curriculum is already inactive', 400);
        }
        $curriculum->update(['status' => 'archived']);

        return $this->success(new CurriculumResource($curriculum->load('courses')), 'Curriculum deactivated successfully', 200);
    }
}
