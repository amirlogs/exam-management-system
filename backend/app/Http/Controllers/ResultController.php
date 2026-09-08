<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResultResource;
use App\Models\CourseOffering;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        $query = Result::with([
            'student.user',
            'student.section',
            'courseOffering.course',
            'courseOffering.semester',
            'publisher',
        ]);

        if ($request->filled('course_offering_id')) {
            $query->where('course_offering_id', $request->input('course_offering_id'));
        }

        if ($request->filled('semester_id')) {
            $query->whereHas('courseOffering', function ($q) use ($request) {
                $q->where('semester_id', $request->input('semester_id'));
            });
        }

        if ($request->filled('letter_grade')) {
            $query->where('letter_grade', $request->input('letter_grade'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('student_number', 'ILIKE', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('first_name', 'ILIKE', "%{$search}%")
                                ->orWhere('last_name', 'ILIKE', "%{$search}%")
                                ->orWhere('email', 'ILIKE', "%{$search}%");
                        });
                })->orWhereHas('courseOffering.course', function ($cq) use ($search) {
                    $cq->where('code', 'ILIKE', "%{$search}%")
                        ->orWhere('title', 'ILIKE', "%{$search}%");
                });
            });
        }

        $results = $query->latest('updated_at')->paginate($perPage);

        return $this->paginate($results, ResultResource::class, 'Results fetched successfully');
    }

    public function show(Result $result)
    {
        $result->load([
            'student.user',
            'student.section',
            'courseOffering.course',
            'courseOffering.semester',
            'publisher',
        ]);

        return $this->success(new ResultResource($result), 'Result fetched successfully');
    }

    public function publish(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_offering_id' => 'required|exists:course_offerings,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $courseOfferingId = $request->input('course_offering_id');
        $results = Result::where('course_offering_id', $courseOfferingId)->get();

        if ($results->isEmpty()) {
            return $this->error(null, 'No results found to publish for this course offering.', 422);
        }

        foreach ($results as $result) {
            $result->update([
                'status' => 'published',
                'published_by' => $request->user()->id,
                'published_at' => now(),
            ]);
        }

        return $this->success(null, "Results for course offering published successfully.");
    }
}
