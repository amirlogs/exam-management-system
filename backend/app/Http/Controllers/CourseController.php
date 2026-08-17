<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Department;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function store(StoreCourseRequest $request)
    {
        $validated = $request->validated();
        $course = Course::create($validated)->load('department');

        return $this->success(new CourseResource($course), 'Course created successfully');
    }

    public function index(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = Course::query();
        RequestFilters::apply($query, $request, ['department_id']);
        $courses = $query->with('department')->paginate($per_page);

        return $this->paginate($courses, CourseResource::class, 'Courses retrieved successfully');
    }

    public function show(Course $course)
    {
        return $this->success(new CourseResource($course->load('department')), 'Course retrieved successfully');
    }

    public function update(UpdateCourseRequest $request, Course $course)
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

        return $this->success(new CourseResource($course), 'Course updated successfully');
    }

    public function destroy(Course $course)
    {
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

    public function archived(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = Course::query();
        RequestFilters::apply($query, $request, ['department_id']);
        $courses = $query->onlyTrashed()->paginate($per_page);

        return $this->paginate($courses, CourseResource::class, 'Archived courses retrieved successfully');
    }
}
