<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Requests\AssignCourseInstructorRequest;
use App\Http\Requests\StoreCourseOfferingRequest;
use App\Http\Requests\UpdateCourseInstructorRequest;
use App\Http\Resources\CourseInstructorResource;
use App\Http\Resources\CourseOfferingResource;
use App\Http\Resources\CourseOfferingSuggestionResource;
use App\Models\CourseInstructor;
use App\Models\CourseOffering;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CourseOfferingController extends Controller
{
    public function generateSuggestions(Request $request)
    {
        $request->validate(['semester_id' => ['required', Rule::exists('semesters', 'id')->withoutTrashed()]]);
        $semester = Semester::find($request->semester_id);

        if (! $semester) {
            return $this->error(null, 'Semester not found', 404);
        }

        $termNumber = $semester->term_number;
        $activeCurriculums = Curriculum::where('status', 'active')->with('program', 'courses')->get();
        $suggestions = [];

        // return $activeCurriculums;

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

        $duplicate = CourseOffering::where('course_id', $validated['course_id'])
            ->where('semester_id', $validated['semester_id'])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->exists();

        if ($duplicate) {
            return $this->error(null, 'Course offering already exists', 409);
        }

        $semester = Semester::find($validated['semester_id']);

        if (! $semester) {
            return $this->error(null, 'Semester not found', 404);
        }

        $offering = CourseOffering::create([
            'course_id' => $validated['course_id'],
            'semester_id' => $validated['semester_id'],
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        $curriculumCourses = CurriculumCourse::query()
            ->where('course_id', $validated['course_id'])
            ->where('semester_number', $semester->term_number)
            ->whereHas('curriculum', function ($query) {
                $query->where('status', 'active');
            })
            ->with('curriculum')->get();

        $sectionIds = collect();

        foreach ($curriculumCourses as $curriculumCourse) {
            $ids = Section::query()
                ->where('semester_id', $semester->id)
                ->where('program_id', $curriculumCourse->curriculum->program_id)
                ->where('year_level', $curriculumCourse->year_level)
                ->pluck('id');

            $sectionIds = $sectionIds->merge($ids);
        }

        $offering->sections()->sync($sectionIds->unique()->values()->all());

        return $this->success(new CourseOfferingResource(
            $offering->load(['course', 'semester', 'sections.program', 'courseInstructors.instructor.user', 'courseInstructors.instructor.department', 'courseInstructors.section'])), 'Course offering created successfully', 201);

    }

    public function attachInstructor(CourseOffering $courseOffering, AssignCourseInstructorRequest $request)
    {
        $validated = $request->validated();

        if ($courseOffering->status === 'cancelled' || $courseOffering->status === 'rejected') {
            return $this->error(null, 'Instructor cannot be assigned to this course offering', 409);
        }

        $sectionBelongsToOffering = $courseOffering
            ->sections()
            ->where('sections.id', $validated['section_id'])
            ->exists();

        if (! $sectionBelongsToOffering) {
            return $this->error(null, 'The selected section does not belong to this course offering', 422);
        }

        $instructor = Instructor::find($validated['instructor_id']);

        if (! $instructor) {
            return $this->error(null, 'Instructor not found', 404);
        }

        if ($instructor->status !== 'active') {
            return $this->error(null, 'Instructor is not active', 422);
        }

        $duplicate = CourseInstructor::where('course_offering_id', $courseOffering->id)
            ->where('section_id', $validated['section_id'])
            ->where('instructor_id', $validated['instructor_id'])
            ->exists();

        if ($duplicate) {
            return $this->error(null, 'Instructor is already assigned to this section', 409);
        }

        $assignment = CourseInstructor::create([
            'course_offering_id' => $courseOffering->id,
            'section_id' => $validated['section_id'],
            'instructor_id' => $validated['instructor_id'],
            'type' => $validated['type'],
            'assigned_at' => now(),
        ]);

        return $this->success(new CourseInstructorResource($assignment->load('instructor.user', 'section')), 'Instructor assigned successfully', 201);
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
        $offerings = $query->with(['course', 'semester', 'sections.program', 'courseInstructors.instructor.user', 'courseInstructors.instructor.department', 'courseInstructors.section'])->paginate($perPage);

        return $this->paginate($offerings, CourseOfferingResource::class, 'Course offerings fetched successfully');
    }

    public function show(CourseOffering $courseOffering)
    {
        $courseOffering->load([
            'course',
            'semester',
            'sections.program',
            'courseInstructors.instructor.user',
            'courseInstructors.instructor.department',
            'courseInstructors.section',
        ]);

        return $this->success(new CourseOfferingResource($courseOffering), 'Course offering fetched successfully');
    }

    public function archived(Request $request)
    {
        $perPage = GetRequestsValidator::validate($request);

        $query = CourseOffering::onlyTrashed();

        RequestFilters::apply($query, $request, ['course_id', 'semester_id', 'status']);

        $offerings = $query->with(['course', 'semester', 'sections.program', 'courseInstructors.instructor.user', 'courseInstructors.instructor.department', 'courseInstructors.section'])->paginate($perPage);

        return $this->paginate($offerings, CourseOfferingResource::class, 'Archived course offerings fetched successfully');
    }

    public function restore($id)
    {
        $offering = CourseOffering::onlyTrashed()->findOrFail($id);
        $offering->restore();

        return $this->success(
            new CourseOfferingResource($offering->load(['course', 'semester', 'sections.program', 'courseInstructors.instructor.user', 'courseInstructors.instructor.department', 'courseInstructors.section'])), 'Course offering restored successfully');
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

    public function changeInstructor(CourseOffering $courseOffering, UpdateCourseInstructorRequest $request)
    {
        $validated = $request->validated();

        if (in_array($courseOffering->status, ['cancelled', 'rejected'])) {
            return $this->error(null, 'Instructor assignment cannot be changed for this course offering', 409);
        }

        $assignment = CourseInstructor::where('course_offering_id', $courseOffering->id)->where(function ($query) use ($validated) {
            if (isset($validated['section_id'])) {
                $query->where('section_id', $validated['section_id']);
            }
        })->first();

        if (! $assignment) {
            return $this->error(null, 'Instructor assignment not found', 404);
        }

        if (isset($validated['section_id'])) {
            $sectionBelongsToOffering = $courseOffering->sections()->where('sections.id', $validated['section_id'])->exists();

            if (! $sectionBelongsToOffering) {
                return $this->error(null, 'The selected section does not belong to this course offering', 422);
            }
        }

        if (isset($validated['instructor_id'])) {
            $instructor = Instructor::find($validated['instructor_id']);

            if (! $instructor) {
                return $this->error(null, 'Instructor not found', 404);
            }

            if ($instructor->status !== 'active') {
                return $this->error(null, 'Instructor is not active', 422);
            }
        }

        $assignment->update([...$validated, 'assigned_at' => now()]);

        return $this->success(new CourseInstructorResource($assignment->load('instructor.user', 'instructor.department', 'section')), 'Instructor assignment updated successfully');
    }

    public function updateInstructorAssignment(CourseOffering $courseOffering, CourseInstructor $assignment, UpdateCourseInstructorRequest $request)
    {
        if ($assignment->course_offering_id !== $courseOffering->id) {
            return $this->error(null, 'Instructor assignment does not belong to this course offering', 404);
        }

        $validated = $request->validated();

        if (in_array($courseOffering->status, ['cancelled', 'rejected'])) {
            return $this->error(null, 'Instructor assignment cannot be changed for this course offering', 409);
        }

        if (isset($validated['section_id'])) {
            $belongs = $courseOffering->sections()->where('sections.id', $validated['section_id'])->exists();

            if (! $belongs) {
                return $this->error(null, 'The selected section does not belong to this course offering', 422);
            }
        }

        if (isset($validated['instructor_id'])) {
            $instructor = Instructor::find($validated['instructor_id']);

            if (! $instructor) {
                return $this->error(null, 'Instructor not found', 404);
            }

            if ($instructor->status !== 'active') {
                return $this->error(null, 'Instructor is not active', 422);
            }
        }

        $assignment->update([
            ...$validated,
            'assigned_at' => now(),
        ]);

        return $this->success(new CourseInstructorResource($assignment->load('instructor.user', 'instructor.department', 'section')), 'Instructor assignment updated successfully');
    }

    public function removeInstructorAssignment(CourseOffering $courseOffering, CourseInstructor $assignment)
    {
        if ($assignment->course_offering_id !== $courseOffering->id) {
            return $this->error(null, 'Instructor assignment does not belong to this course offering', 404);
        }

        if ($courseOffering->status === 'cancelled') {
            return $this->error(null, 'Instructor assignment cannot be removed from a cancelled offering', 409);
        }

        $assignment->delete();

        return $this->success(null, 'Instructor assignment removed successfully');
    }

    public function approve(CourseOffering $courseOffering)
    {
        if ($courseOffering->status !== 'draft') {
            return $this->error(null, 'Only draft offerings can be approved', 409);
        }

        if (! $courseOffering->sections()->exists()) {
            return $this->error(null, 'Cannot approve offering because no sections are associated with it', 409);
        }

        DB::transaction(function () use ($courseOffering) {
            $courseOffering->approve();

            $sectionIds = $courseOffering->sections()->pluck('sections.id');

            $students = Student::whereIn('section_id', $sectionIds)->where('status', 'active')->get(['id']);
            foreach ($students as $student) {
                Enrollment::firstOrCreate(
                    ['student_id' => $student->id, 'course_offering_id' => $courseOffering->id],
                    ['status' => 'active']
                );
            }
        });

        return $this->success(new CourseOfferingResource($courseOffering->refresh()->load(['course', 'semester', 'sections', 'instructors.user'])), 'Course offering approved and students enrolled successfully');
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

    public function reopen(CourseOffering $courseOffering)
    {
        if (! in_array($courseOffering->status, ['rejected', 'cancelled'])) {
            return $this->error(null, 'Only rejected or cancelled offerings can be reopened', 409);
        }

        $duplicate = CourseOffering::where('course_id', $courseOffering->course_id)
            ->where('semester_id', $courseOffering->semester_id)
            ->where('id', '!=', $courseOffering->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->with('course')
            ->first();

        if ($duplicate) {
            return $this->error(
                null,
                "Cannot reopen: an active offering for {$duplicate->course->code} already exists in this semester (status: {$duplicate->status}). Archive or cancel that one first.",
                409
            );
        }

        $courseOffering->update([
            'status' => 'draft',
            'rejection_reason' => null,
        ]);

        return $this->success(
            new CourseOfferingResource($courseOffering->load('course', 'semester', 'sections', 'instructors')),
            'Course offering reopened as draft'
        );
    }
}
