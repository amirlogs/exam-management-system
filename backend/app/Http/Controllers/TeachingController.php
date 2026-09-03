<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Filters\RequestFilters;
use App\Http\Resources\TeachingDetailResource;
use App\Http\Resources\TeachingResource;
use App\Models\CourseOffering;
use App\Models\Student;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class TeachingController extends Controller
{
    public function index(Request $request)
    {
        $instructor = $request->user()->instructor;
        if (! $instructor) {
            return $this->error(null, 'Instructor profile not found.', 403);
        }

        $perPage = GetRequestsValidator::validate($request);

        $query = CourseOffering::whereHas('courseInstructors', fn($query) => $query ->where('instructor_id', $instructor->id))
            ->with([ 'course', 'semester', 'courseInstructors' => fn($query) => $query ->where('instructor_id', $instructor->id)
                ->with('section.program'), ]);

        RequestFilters::apply($query, $request, [ 'semester_id', 'course_id', 'status', ]);

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->whereHas('course', function ($query) use ($search) {
                $query->where('code', 'ILIKE', "%{$search}%")
                    ->orWhere('name', 'ILIKE', "%{$search}%");
            });
        }

        $teaching = $query ->latest() ->paginate($perPage);
        return $this->paginate($teaching, TeachingResource::class, 'Teaching assignments fetched successfully');
    }
    public function show(Request $request, CourseOffering $courseOffering)
    {
        $instructor = $request->user()->instructor;

        if (! $instructor) {
            return $this->error(null, 'Instructor profile not found.', 403);
        }

        $hasAssignment = $courseOffering ->courseInstructors() ->where('instructor_id', $instructor->id) ->exists();

        if (! $hasAssignment) {
            return $this->error(null, 'You are not assigned to this course offering.', 403);
        }

        $courseOffering->load([ 'course', 'semester', 'courseInstructors' => function ($query) use ($instructor) {
            $query ->where('instructor_id', $instructor->id) ->with('section.program');
        }, 'exams.courseOffering', ]);

        $sectionIds = $courseOffering ->courseInstructors ->pluck('section_id') ->filter() ->unique();
        $students = Student::with([ 'user', 'section', ]) ->whereIn('section_id', $sectionIds) ->get();
        $courseOffering->setRelation('students', $students);

        return $this->success(new TeachingDetailResource($courseOffering), 'Teaching assignment fetched successfully');
    }
}
