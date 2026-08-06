<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $enrollments = Enrollment::with('student.user', 'courseOffering.course')
            ->when($request->student_id, fn ($q, $id) => $q->where('student_id', $id))
            ->when($request->course_offering_id, fn ($q, $id) => $q->where('course_offering_id', $id))
            ->get();

        return $this->success($enrollments, 'Enrollments fetched successfully');
    }

    public function update(Enrollment $enrollment, Request $request)
    {
        $request->validate(['status' => 'required|in:active,dropped,completed']);
        $enrollment->update(['status' => $request->status]);

        return $this->success($enrollment, 'Enrollment updated successfully');
    }
}
