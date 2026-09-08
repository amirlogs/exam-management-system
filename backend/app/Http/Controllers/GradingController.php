<?php

namespace App\Http\Controllers;

use App\Http\Resources\GradeResource;
use App\Jobs\GradeExamJob;
use App\Models\Answer;
use App\Models\CourseOffering;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\GradeVerification;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GradingController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        $query = Grade::with([
            'student.user',
            'courseOffering.course',
            'courseOffering.semester',
            'exam',
            'grader',
            'verifications.verifier',
        ]);

        if ($request->filled('course_offering_id')) {
            $query->where('course_offering_id', $request->input('course_offering_id'));
        }

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->input('exam_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('student', function ($sq) use ($search) {
                $sq->where('student_number', 'ILIKE', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'ILIKE', "%{$search}%")
                            ->orWhere('last_name', 'ILIKE', "%{$search}%")
                            ->orWhere('email', 'ILIKE', "%{$search}%");
                    });
            });
        }

        $grades = $query->latest('updated_at')->paginate($perPage);

        return $this->paginate($grades, GradeResource::class, 'Grades fetched successfully');
    }

    public function courseOfferingGrades(CourseOffering $courseOffering, Request $request)
    {
        $request->merge(['course_offering_id' => $courseOffering->id]);

        return $this->index($request);
    }

    public function show(Grade $grade)
    {
        $grade->load([
            'student.user',
            'courseOffering.course',
            'courseOffering.semester',
            'exam',
            'grader',
            'verifications.verifier',
        ]);

        return $this->success(new GradeResource($grade), 'Grade fetched successfully');
    }
    public function autoGrade(Exam $exam, Request $request)
    {
        if ($exam->status !== 'completed') {
            return $this->error(null, 'Exam must be completed to be graded.', 422);
        }

        $exam->update(['grading_status' => 'in_progress']);

        GradeExamJob::dispatch($exam, $request->user());

        return $this->success(null, 'Exam grading has been started. Results will be available when grading is completed.');
    }

    public function submissions(Exam $exam, Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        $query = $exam->attempts()
            ->with(['student.user'])
            ->withCount([
                'answers',
                'answers as graded_answers_count' => fn ($q) => $q->whereNotNull('marks_awarded'),
                'answers as pending_grading_count' => fn ($q) => $q->whereNull('marks_awarded'),
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('student', function ($sq) use ($search) {
                $sq->where('student_number', 'ILIKE', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'ILIKE', "%{$search}%")
                            ->orWhere('last_name', 'ILIKE', "%{$search}%")
                            ->orWhere('email', 'ILIKE', "%{$search}%");
                    });
            });
        }

        $attempts = $query->latest('submitted_at')->paginate($perPage);

        return $this->paginate($attempts, \App\Http\Resources\ExamAttemptResource::class, 'Submissions fetched successfully');
    }

    public function submissionDetail(Exam $exam, \App\Models\ExamAttempt $attempt)
    {
        if ($attempt->exam_id !== $exam->id) {
            return $this->error(null, 'Attempt does not belong to this exam.', 404);
        }

        $attempt->load([
            'student.user',
            'answers.examQuestion',
            'answers.question.options',
        ]);

        return $this->success($attempt, 'Submission details fetched successfully');
    }

    public function gradeAnswer(Answer $answer, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marks_awarded' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $maxMarks = (float) ($answer->examQuestion?->marks ?? 0);
        $marksAwarded = (float) $request->input('marks_awarded');

        if ($marksAwarded > $maxMarks) {
            return $this->error(
                ['marks_awarded' => ["Marks awarded cannot exceed maximum marks of {$maxMarks}."]],
                'Validation error',
                422
            );
        }

        DB::transaction(function () use ($answer, $marksAwarded, $request) {
            $answer->update([
                'marks_awarded' => $marksAwarded,
                'is_correct' => $marksAwarded > 0,
                'graded_by' => $request->user()->id,
                'graded_at' => now(),
            ]);

            $attempt = $answer->examAttempt;
            $exam = $attempt->exam;

            // Recalculate total attempt score
            $totalScore = (float) $attempt->answers()->whereNotNull('marks_awarded')->sum('marks_awarded');
            $hasUngraded = $attempt->answers()->whereNull('marks_awarded')->exists();

            $attempt->update([
                'score' => $totalScore,
                'status' => $hasUngraded ? 'completed' : 'graded',
            ]);

            // Synchronize Grade
            Grade::updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'student_id' => $attempt->student_id,
                    'course_offering_id' => $exam->course_offering_id,
                ],
                [
                    'score' => $totalScore,
                    'graded_by' => $request->user()->id,
                    'status' => 'pending_verification',
                ]
            );

            // Update exam grading_status
            $pendingExamAnswers = Answer::whereHas('examAttempt', fn($q) => $q->where('exam_id', $exam->id))
                ->whereNull('marks_awarded')
                ->count();

            $exam->update([
                'grading_status' => $pendingExamAnswers > 0 ? 'in_progress' : 'completed',
            ]);
        });

        return $this->success($answer->fresh(['examQuestion', 'question']), 'Answer graded successfully');
    }

    public function submitVerification(CourseOffering $courseOffering, Request $request)
    {
        $query = Grade::where('course_offering_id', $courseOffering->id);

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->input('exam_id'));
        }

        $count = $query->count();
        if ($count === 0) {
            return $this->error(null, 'No grades found to submit for verification.', 422);
        }

        $query->update(['status' => 'pending_verification']);

        return $this->success(null, "{$count} grade(s) submitted for verification successfully.");
    }

    public function verify(Grade $grade, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:verified,rejected',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        DB::transaction(function () use ($grade, $request) {
            GradeVerification::create([
                'grade_id' => $grade->id,
                'verified_by' => $request->user()->id,
                'status' => $request->input('status'),
                'comment' => $request->input('comment'),
                'verified_at' => now(),
            ]);

            $grade->update(['status' => $request->input('status')]);
        });

        return $this->success($grade->fresh(['verifications']), 'Grade verification recorded successfully');
    }

    public function publish(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_offering_id' => 'required|exists:course_offerings,id',
            'exam_id' => 'nullable|exists:exams,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $query = Grade::where('course_offering_id', $request->input('course_offering_id'));

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->input('exam_id'));
        }

        $unverifiedCount = (clone $query)->where('status', '!=', 'verified')->count();
        if ($unverifiedCount > 0) {
            return $this->error(null, "Cannot publish: {$unverifiedCount} grade(s) have not been verified yet.", 422);
        }

        $grades = $query->get();
        if ($grades->isEmpty()) {
            return $this->error(null, 'No verified grades found to publish.', 422);
        }

        DB::transaction(function () use ($grades, $request) {
            foreach ($grades as $grade) {
                $grade->update(['status' => 'published']);
            }

            $courseOfferingId = $request->input('course_offering_id');
            $studentGrades = Grade::where('course_offering_id', $courseOfferingId)
                ->where('status', 'published')
                ->get()
                ->groupBy('student_id');

            $totalPossibleMarks = (float) Exam::where('course_offering_id', $courseOfferingId)->sum('total_marks');

            foreach ($studentGrades as $studentId => $gList) {
                $totalScore = (float) $gList->sum('score');
                $percentage = $totalPossibleMarks > 0 ? ($totalScore / $totalPossibleMarks) * 100 : $totalScore;

                $letterGrade = 'F';
                if ($percentage >= 90) {
                    $letterGrade = 'A';
                } elseif ($percentage >= 85) {
                    $letterGrade = 'A-';
                } elseif ($percentage >= 80) {
                    $letterGrade = 'B+';
                } elseif ($percentage >= 75) {
                    $letterGrade = 'B';
                } elseif ($percentage >= 70) {
                    $letterGrade = 'B-';
                } elseif ($percentage >= 65) {
                    $letterGrade = 'C+';
                } elseif ($percentage >= 60) {
                    $letterGrade = 'C';
                } elseif ($percentage >= 50) {
                    $letterGrade = 'D';
                }

                Result::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'course_offering_id' => $courseOfferingId,
                    ],
                    [
                        'total_score' => $totalScore,
                        'letter_grade' => $letterGrade,
                        'status' => 'published',
                        'published_by' => $request->user()->id,
                        'published_at' => now(),
                    ]
                );
            }
        });

        return $this->success(null, 'Grades published and student results calculated successfully.');
    }
}
