<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreCourseOfferingRequest;
use App\Http\Resources\CourseOfferingResource;
use App\Http\Resources\CourseOfferingSuggestionResource;
use App\Models\CourseOffering;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseOfferingController extends Controller
{
    public function generateSuggestions(Request $request)
    {
        $request->validate(['semester_id' => ['required', Rule::exists('semesters', 'id')->withoutTrashed()]]);
        $semester = Semester::find($request->semester_id);
        $termNumber = $semester->term_number;

        $activeCurriculums = Curriculum::where('status', 'active')->with('program', 'courses')->get();
        $suggestions = [];

        foreach ($activeCurriculums as $curriculum) {
            $requiredCourses = CurriculumCourse::where('curriculum_id', $curriculum->id)
                ->where('semester_number', $termNumber)
                ->with('course')
                ->get();

            foreach ($requiredCourses as $cc) {
                $hasSection = Section::where('program_id', $curriculum->program_id)
                    ->where('semester_id', $semester->id)
                    ->where('year_level', $cc->year_level)
                    ->exists();

                if (! $hasSection) {
                    continue;
                }

                $alreadyOffered = CourseOffering::where('course_id', $cc->course_id)
                    ->where('semester_id', $semester->id)
                    ->whereNotIn('status', ['cancelled', 'rejected'])
                    ->exists();

                if ($alreadyOffered) {
                    continue;
                }

                $suggestions[] = [
                    'course_id' => $cc->course_id,
                    'course_code' => $cc->course->code,
                    'course_name' => $cc->course->name,
                    'program_id' => $curriculum->program_id,
                    'program_name' => $curriculum->program->name,
                    'year_level' => $cc->year_level,
                ];
            }
        }

        return $this->success(CourseOfferingSuggestionResource::collection($suggestions), 'Course offering suggestions generated successfully');
    }

    public function store(StoreCourseOfferingRequest $request)
    {
        $validated = $request->validated();

        $duplicate = CourseOffering::where('course_id', $request->course_id)
            ->where('semester_id', $validated['semester_id'])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->exists();

        if ($duplicate) {
            return $this->error(null, 'Course offering already exists', 409);
        }

        $offering = CourseOffering::create([
            'course_id' => $request->course_id,
            'semester_id' => $request->semester_id,
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        return $this->success(new CourseOfferingResource($offering->load('course', 'semester')), 'Course offering created successfully', 201);
    }

    public function attachSections(CourseOffering $courseOffering, Request $request)
    {
        $request->validate(['section_id' => 'required|exists:sections,id']);

        $section = Section::find($request->section_id);
        if (! $section) {
            return $this->error(null, 'Section not found', 404);        }

        $courseOffering->sections()->syncWithoutDetaching([$section->id]);

        return $this->success(new CourseOfferingResource($courseOffering->load('sections')), 'Section attached to course offering successfully');
    }

    public function detachSection(CourseOffering $courseOffering, Section $section)
    {
        $courseOffering->sections()->detach($section->id);

        return $this->success(new CourseOfferingResource($courseOffering->load('sections')), 'Section removed from course offering successfully');
    }

    public function attachInstructors(CourseOffering $courseOffering, Request $request)
    {
        // unchanged
        $request->validate([
            'instructor_id' => 'required|exists:users,id',
            'type' => 'required|in:lead_instructor,instructor',
        ]);

        $courseOffering->instructors()->syncWithoutDetaching([
            $request->instructor_id => ['type' => $request->type, 'assigned_at' => now()],
        ]);

        return $this->success(new CourseOfferingResource($courseOffering->load('instructors')), 'Instructor assigned successfully');
    }

    public function removeInstructor(CourseOffering $courseOffering, $instructorId)
    {
        $courseOffering->instructors()->detach($instructorId);

        return $this->success(new CourseOfferingResource($courseOffering->load('instructors')), 'Instructor removed successfully');
    }

    public function index(Request $request)
    {
        $perPage = GetRequestsValidator::validate($request);
        $query = CourseOffering::query();
        RequestFilters::apply($query, $request, ['course_id', 'semester_id', 'status']);
        $offerings = $query->with('course', 'semester', 'sections', 'instructors')
            ->paginate($perPage);

        return $this->paginate($offerings, CourseOfferingResource::class, 'Course offerings fetched successfully');
    }

    public function archived(Request $request)
    {
        $perPage = GetRequestsValidator::validate($request);
        $query = CourseOffering::onlyTrashed();
        RequestFilters::apply($query, $request, ['course_id', 'semester_id', 'status']);
        $offerings = $query->with('course', 'semester', 'sections', 'instructors')->paginate($perPage);

        return $this->paginate($offerings, CourseOfferingResource::class, 'Archived course offerings fetched successfully');
    }

    public function restore($id)
    {
        $offering = CourseOffering::onlyTrashed()->findOrFail($id);
        $offering->restore();

        return $this->success(new CourseOfferingResource($offering->load('course', 'semester')), 'Course offering restored successfully');
    }

    public function update(CourseOffering $courseOffering, Request $request)
    {
        $request->validate([
            'course_id' => 'sometimes|exists:courses,id',
            'semester_id' => 'sometimes|exists:semesters,id',
        ]);

        if ($courseOffering->status !== 'draft') {
            return $this->error(null, 'Only draft offerings can be edited', 409);
        }
        $courseOffering->update($request->only('course_id', 'semester_id'));

        return $this->success(new CourseOfferingResource($courseOffering->load('course', 'semester')), 'Course offering updated successfully');
    }

    public function changeInstructor(CourseOffering $courseOffering, Request $request)
    {
        $request->validate([
            'instructor_id' => 'required|exists:users,id',
            'type' => 'required|in:lead_instructor,instructor',
        ]);

        $courseOffering->instructors()->wherePivot('type', $request->type)->detach();
        $courseOffering->instructors()->attach($request->instructor_id, ['type' => $request->type, 'assigned_at' => now()]);

        return $this->success(new CourseOfferingResource($courseOffering->load('instructors')), 'Instructor changed successfully');
    }

    public function approve(CourseOffering $courseOffering)
    {
        if ($courseOffering->status !== 'draft') {
            return $this->error(null, 'Only draft offerings can be approved', 409);
        }
        $courseOffering->approve();

        return $this->success(new CourseOfferingResource($courseOffering->load('instructors', 'course')), 'Course offering approved successfully');
    }

    public function reject(CourseOffering $courseOffering, Request $request)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if ($courseOffering->status !== 'draft') {
            return $this->error(null, 'Only draft offerings can be rejected', 409);
        }
        $courseOffering->reject($request->reason);

        return $this->success(new CourseOfferingResource($courseOffering->load('instructors', 'course')), 'Course offering rejected successfully');
    }

    public function cancel(CourseOffering $courseOffering)
    {
        if (in_array($courseOffering->status, ['cancelled', 'rejected'])) {
            return $this->error(null, 'Offering is already cancelled or rejected', 409);
        }
        $courseOffering->cancel();

        return $this->success(new CourseOfferingResource($courseOffering->refresh()->load('course', 'semester', 'instructors')), 'Course offering cancelled successfully');
    }

    public function destroy(CourseOffering $courseOffering)
    {
        $courseOffering->delete();

        return $this->success(null, 'Course offering archived successfully');
    }

    public function enrollSection(CourseOffering $courseOffering)
    {
        if ($courseOffering->status !== 'approved') {
            return $this->error(null, 'Only approved offerings can be enrolled', 409);
        }

        $sectionIds = $courseOffering->sections()->pluck('sections.id');
        $students = Student::whereIn('section_id', $sectionIds)->where('status', 'active')->get();

        $enrolledCount = 0;
        foreach ($students as $student) {
            $created = Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'course_offering_id' => $courseOffering->id],
                ['status' => 'active']
            );
            if ($created->wasRecentlyCreated) {
                $enrolledCount++;
            }
        }

        return $this->success(['enrolled_count' => $enrolledCount], 'Students enrolled successfully');
    }
}
