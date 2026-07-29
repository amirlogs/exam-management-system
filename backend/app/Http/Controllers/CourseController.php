<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeRequest;
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
}
