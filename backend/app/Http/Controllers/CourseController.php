<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeRequest;
use App\Http\Requests\UpdateCollegeRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Department;

class CourseController extends Controller
{
    public function store(StoreCollegeRequest $request)
    {
        $validated = $request->validated();
        $department = Department::find($validated['department_id']);

        if (! $department) {
            $this->error('', 'Department not found', 404);
        }

        $course = $department->courses()->create($validated);

        return $this->success($course, 'Course created successfully');
    }

    public function index()
    {
        $courses = Course::all();

        if ($courses->isEmpty()) {
            $this->error('', 'No courses found', 404);
        }

        return $this->success(CourseResource::collection($courses), 'Courses retrieved successfully');
    }

    public function update(UpdateCollegeRequest $request, Course $course)
    {
        $validated = $request->validated();

        if (! $course) {
            $this->error('', 'Course not found', 404);
        }

        if ($request->has('department_id')) {
            $department = Department::find($validated['department_id']);
            if (! $department) {
                $this->error('', 'Department not found', 404);
            }
        }

        $course->update($validated);

        return $this->success($course, 'Course updated successfully');
    }

    public function destroy(Course $course)
    {
        if (! $course) {
            $this->error('', 'Course not found', 404);
        }

        $course->delete();

        return $this->success('', 'Course deleted successfully');
    }

    public function restore(string $courseId)
    {
        $course = Course::onlyTrashed()->find($courseId);
        if (! $course) {
            $this->error('', 'Program not found or is not in the trash', 404);
        }
        $course->restore();

        return $this->success(new CourseResource($course), 'Course restored successfully');
    }
}
