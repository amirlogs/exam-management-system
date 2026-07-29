<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurriculumRequest;
use App\Models\Course;

class CurriculumController extends Controller
{
    public function store(StoreCurriculumRequest $request)
    {
        $validated = $request->validated();

        $course = Course::find($validated['course_id']);
        if (! $course) {
            return $this->error('', 'Course not found', 404);
        }

        $carriculum = $course->curriculum()->create($validated);

        return $this->success($carriculum, 'Curriculum created successfully', 201);

    }
}
