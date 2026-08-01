<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddCourseRequest;
use App\Http\Requests\StoreCurriculumRequest;
use App\Http\Requests\UpdateAddCourseRequest;
use App\Http\Requests\UpdateCurriculumRequest;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;

class CurriculumController extends Controller
{
    public function store(StoreCurriculumRequest $request)
    {
        $validated = $request->validated();
        $curriculum = Curriculum::create($validated);

        return $this->success($curriculum, 'Curriculum created successfully', 201);
    }

    public function index()
    {
        $curriculums = Curriculum::with('curriculumCourses')->get();

        return $this->success($curriculums, 'Curriculums retrieved successfully', 200);
    }

    public function update(Curriculum $curriculum, UpdateCurriculumRequest $request)
    {

        $validated = $request->validated();

        return $this->success($curriculum, 'Curriculum updated successfully', 200);
    }

    public function addCourse(StoreAddCourseRequest $request, Curriculum $curriculum)
    {
        $validated = $request->validated();

        if ($curriculum->curriculumCourses()->where('course_id', $validated['course_id'])->exists()) {
            return $this->error('Course already exists in curriculum', 400);
        }

        $curriculum->curriculumCourses()->create($validated);

        return $this->success($curriculum, 'Course added to curriculum successfully', 201);
    }

    public function updateCourse(CurriculumCourse $curriculumCourse, UpdateAddCourseRequest $request)
    {
        $validated = $request->validated();
        $curriculumCourse->update($validated);

        return $this->success($curriculumCourse, 'Course updated successfully', 200);
    }

    public function removeCourse(CurriculumCourse $curriculumCourse)
    {
        $curriculumCourse->delete();

        return $this->success(null, 'Course removed from curriculum successfully', 200);
    }

    public function activate(Curriculum $curriculum)
    {
        if ($curriculum->isActive()) {
            return $this->error('Curriculum is already active', 400);
        }

        $curriculum->update(['status' => 'active']);

        return $this->success($curriculum, 'Curriculum activated successfully', 200);
    }

    public function deactivate(Curriculum $curriculum)
    {
        if (! $curriculum->isActive()) {
            return $this->error('Curriculum is already inactive', 400);
        }
        $curriculum->update(['status' => 'archived']);

        return $this->success($curriculum, 'Curriculum deactivated successfully', 200);
    }
}
