<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseOfferingController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\GradingController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuestionFlagController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\Student\StudentResultController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ============ AUTH ============
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    // ============ UNIVERSITY STRUCTURE ============
    Route::post('/universities', [UniversityController::class, 'store'])->middleware('permission:university.create');
    Route::get('/universities', [UniversityController::class, 'index']);
    Route::get('/universities/archived', [UniversityController::class, 'archived']);
    Route::get('/universities/{university}', [UniversityController::class, 'show']);
    Route::patch('/universities/{university}', [UniversityController::class, 'update'])->middleware('permission:university.update');
    Route::delete('/universities/{university}', [UniversityController::class, 'destroy'])->middleware('permission:university.archive');
    Route::post('/universities/{university}/restore', [UniversityController::class, 'restore'])->middleware('permission:university.archive');

    Route::post('/colleges', [CollegeController::class, 'store'])->middleware('permission:college.create');
    Route::get('/colleges', [CollegeController::class, 'index']);
    Route::get('/colleges/archived', [CollegeController::class, 'archived']);
    Route::get('/colleges/{college}', [CollegeController::class, 'show']);
    Route::patch('/colleges/{college}', [CollegeController::class, 'update'])->middleware('permission:college.update');
    Route::delete('/colleges/{college}', [CollegeController::class, 'destroy'])->middleware('permission:college.archive');
    Route::post('/colleges/{college}/restore', [CollegeController::class, 'restore'])->middleware('permission:college.archive');

    Route::post('/departments', [DepartmentController::class, 'store'])->middleware('permission:department.create');
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/departments/archived', [DepartmentController::class, 'archived'])->middleware('permission:department.archive');
    Route::get('/departments/{department}', [DepartmentController::class, 'show']);
    Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->middleware('permission:department.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:department.archive');
    Route::post('/departments/{department}/restore', [DepartmentController::class, 'restore'])->middleware('permission:department.archive');

    Route::post('/programs', [ProgramController::class, 'store'])->middleware('permission:program.create');
    Route::get('/programs', [ProgramController::class, 'index']);
    Route::get('/programs/archived', [ProgramController::class, 'archived']);
    Route::patch('/programs/{program}', [ProgramController::class, 'update'])->middleware('permission:program.update');
    Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])->middleware('permission:program.archive');
    Route::post('/programs/{program}/restore', [ProgramController::class, 'restore'])->middleware('permission:program.archive');

    Route::post('/courses', [CourseController::class, 'store'])->middleware('permission:course.create');
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/archived', [CourseController::class, 'archived']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::patch('/courses/{course}', [CourseController::class, 'update'])->middleware('permission:course.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->middleware('permission:course.archive');
    Route::post('/courses/{course}/restore', [CourseController::class, 'restore'])->middleware('permission:course.archive');

    // =================== Roles || Permissions || Users ==========
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:role.create');
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:role.assign');
    Route::patch('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:role.update');
    Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermission'])->middleware('permission:permission.assign');
    Route::post('/roles/{role}/permissions/remove', [RoleController::class, 'removePermission'])->middleware('permission:permission.assign');
    // FIXED: was singular '/role/{role}' — typo, now matches the rest of this resource
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:role.archive');
    Route::post('/roles/{role}/restore', [RoleController::class, 'restore'])->middleware('permission:role.archive');

    Route::post('/users', [UserController::class, 'store'])->middleware('permission:user.create');
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:user.view');
    Route::patch('/users/{user}', [UserController::class, 'update'])->middleware('permission:user.update');
    Route::post('/users/{user}/roles', [UserController::class, 'assignRole'])->middleware('permission:role.assign');
    // FIXED: was singular '/user/{user}/roles/{userRole}' — typo
    Route::delete('/users/{user}/roles/{userRole}', [UserController::class, 'removeRole'])->middleware('permission:role.remove');

    // =================== CURRICULUM ===================
    Route::post('/curriculums', [CurriculumController::class, 'store'])->middleware('permission:curriculum.create');
    Route::get('/curriculums', [CurriculumController::class, 'index']);
    Route::patch('/curriculums/{curriculum}', [CurriculumController::class, 'update'])->middleware('permission:curriculum.update');
    Route::post('/curriculums/{curriculum}/activate', [CurriculumController::class, 'activate'])->middleware('permission:curriculum_version.activate');
    Route::post('/curriculums/{curriculum}/deactivate', [CurriculumController::class, 'deactivate'])->middleware('permission:curriculum_version.activate');
    Route::post('/curriculums/{curriculum}/courses', [CurriculumController::class, 'addCourse'])->middleware('permission:curriculum_course.add');
    Route::patch('/curriculums/courses/{curriculumCourse}', [CurriculumController::class, 'updateCourse'])->middleware('permission:curriculum_course.update');
    Route::delete('/curriculums/courses/{curriculumCourse}', [CurriculumController::class, 'removeCourse'])->middleware('permission:curriculum_course.remove');

    // ==================== SEMESTERS | SECTIONS ====================
    Route::post('/semesters', [SemesterController::class, 'store'])->middleware('permission:semester.create');
    Route::get('/semesters', [SemesterController::class, 'index']);
    Route::get('/semesters/archived', [SemesterController::class, 'archived'])->middleware('permission:semester.archive');
    Route::patch('/semesters/{semester}', [SemesterController::class, 'update'])->middleware('permission:semester.update');
    Route::post('/semesters/{semester}/open', [SemesterController::class, 'open'])->middleware('permission:semester.open');
    Route::post('/semesters/{semester}/close', [SemesterController::class, 'close'])->middleware('permission:semester.close');
    Route::delete('/semesters/{semester}', [SemesterController::class, 'archive'])->middleware('permission:semester.archive');
    Route::post('/semesters/{semester}/restore', [SemesterController::class, 'restore'])->middleware('permission:semester.archive');

    Route::post('/sections', [SectionController::class, 'store'])->middleware('permission:section.import');
    Route::get('/sections', [SectionController::class, 'index']);

    // ===================== IMPORT (students, sections, instructors, users, questions)
    Route::post('/imports/{type}', [ImportController::class, 'store']);
    Route::get('/imports/{importHistory}', [ImportController::class, 'show']);
    Route::patch('/imports/{importHistory}/rows/{rowNumber}', [ImportController::class, 'updateRow']);
    Route::post('/imports/{importHistory}/confirm', [ImportController::class, 'confirm']);

    // ======================= COURSE OFFERING =====================
    Route::post('/course-offerings/suggestions/generate', [CourseOfferingController::class, 'generateSuggestions'])->middleware('permission:course_offering.create');
    Route::post('/course-offerings', [CourseOfferingController::class, 'store'])->middleware('permission:course_offering.create');
    Route::post('/course-offerings/{courseOffering}/sections', [CourseOfferingController::class, 'attachSections'])->middleware('permission:course_offering.update');
    Route::post('/course-offerings/{courseOffering}/instructors', [CourseOfferingController::class, 'attachInstructors'])->middleware('permission:course_offering.assign_instructor');
    Route::get('/course-offerings', [CourseOfferingController::class, 'index']);
    Route::patch('/course-offerings/{courseOffering}', [CourseOfferingController::class, 'update'])->middleware('permission:course_offering.update');
    Route::patch('/course-offerings/{courseOffering}/instructors/{instructorId}', [CourseOfferingController::class, 'changeInstructor'])->middleware('permission:course_offering.change_instructor');
    Route::post('/course-offerings/{courseOffering}/approve', [CourseOfferingController::class, 'approve'])->middleware('permission:course_offering.approve');
    Route::post('/course-offerings/{courseOffering}/reject', [CourseOfferingController::class, 'reject'])->middleware('permission:course_offering.reject');
    Route::post('/course-offerings/{courseOffering}/cancel', [CourseOfferingController::class, 'cancel'])->middleware('permission:course_offering.cancel');
    Route::delete('/course-offerings/{courseOffering}', [CourseOfferingController::class, 'destroy'])->middleware('permission:course_offering.archive');

    // ========================== ENROLLMENT =======================
    Route::post('/course-offerings/{courseOffering}/enroll', [CourseOfferingController::class, 'enrollSection'])->middleware('permission:course_offering.update');
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->middleware('permission:course_offering.update');
    Route::get('/enrollments', [EnrollmentController::class, 'index']);
    Route::patch('/enrollments/{enrollment}', [EnrollmentController::class, 'update'])->middleware('permission:course_offering.update');

    // ========================== QUESTION BANK ===================
    Route::post('/courses/{courseId}/questions', [QuestionController::class, 'store'])->middleware('permission:question.create');
    Route::get('/questions', [QuestionController::class, 'index']);
    Route::get('/questions/{question}', [QuestionController::class, 'show']);
    Route::patch('/questions/{question}', [QuestionController::class, 'update'])->middleware('permission:question.update');
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->middleware('permission:question.archive');
    Route::post('/questions/{question}/restore', [QuestionController::class, 'restore'])->middleware('permission:question.restore');

    Route::post('/questions/{question}/flags', [QuestionFlagController::class, 'store']);
    Route::get('/questions/{question}/flags', [QuestionFlagController::class, 'index']);
    Route::patch('/question-flags/{flag}/resolve', [QuestionFlagController::class, 'resolve'])->middleware('permission:question.update');

    // ======================== EXAMS(admin/instructor side) ========================
    Route::post('/course-offerings/{courseOffering}/exams', [ExamController::class, 'store'])->middleware('permission:exam.create');
    Route::patch('/exams/{exam}/composition', [ExamController::class, 'updateComposition'])->middleware('permission:exam.update');
    Route::post('/exams/{exam}/questions', [ExamController::class, 'addQuestion'])->middleware('permission:exam.update');
    Route::post('/exams/{exam}/question-imports/{importHistory}/confirm', [ExamController::class, 'confirmImportedQuestions'])->middleware('permission:exam.update');
    Route::delete('/exams/{exam}/questions/{examQuestion}', [ExamController::class, 'removeQuestion'])->middleware('permission:exam.update');
    Route::get('/exams/{exam}/questions', [ExamController::class, 'questions']);
    Route::get('/exams/{exam}/composition-status', [ExamController::class, 'compositionStatus']);
    Route::post('/exams/{exam}/submit-approval', [ExamController::class, 'submitApproval'])->middleware('permission:exam.submit_approval');
    Route::post('/exams/{exam}/revert-to-draft', [ExamController::class, 'revertToDraft'])->middleware('permission:exam.update');
    Route::post('/exams/{exam}/approve', [ExamController::class, 'approve'])->middleware('permission:exam.approve');
    Route::post('/exams/{exam}/reject', [ExamController::class, 'reject'])->middleware('permission:exam.reject');
    Route::post('/exams/{exam}/schedule', [ExamController::class, 'schedule'])->middleware('permission:exam.schedule');
    Route::patch('/exams/{exam}/schedule', [ExamController::class, 'updateSchedule'])->middleware('permission:exam.update_schedule');
    Route::post('/exams/{exam}/extend-time', [ExamController::class, 'extendTime'])->middleware('permission:exam.extend_time');
    Route::post('/exams/{exam}/activate', [ExamController::class, 'activate'])->middleware('permission:exam.schedule'); // admin "start now" — shares schedule's permission, not a new one
    Route::post('/exams/{exam}/publish', [ExamController::class, 'publish'])->middleware('permission:exam.publish'); // admin publish
    Route::post('/exams/{exam}/end', [ExamController::class, 'end'])->middleware('permission:exam.close');
    Route::post('/exams/{exam}/cancel', [ExamController::class, 'cancel'])->middleware('permission:exam.cancel');
    Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->middleware('permission:exam.archive');

    // ====================== GRADING =============================
    Route::post('/exams/{exam}/auto-grade', [GradingController::class, 'autoGrade'])->middleware('permission:grade.auto_grade');
    Route::patch('/answers/{answer}/grade', [GradingController::class, 'gradeAnswer'])->middleware('permission:grade.create');
    Route::post('/course-offerings/{courseOffering}/grades/submit-verification', [GradingController::class, 'submitVerification'])->middleware('permission:grade.submit_verification');
    Route::post('/grades/{grade}/verify', [GradingController::class, 'verify'])->middleware('permission:grade.verify');
    Route::post('/grades/publish', [GradingController::class, 'publish'])->middleware('permission:grade.publish');

    // ====================== RESULTS =============================
    // Route::post('/results/generate', [ResultController::class, 'generate'])->middleware('permission:result.generate');
    // Route::post('/results/publish', [ResultController::class, 'publish'])->middleware('permission:result.publish');
    // Route::get('/results', [ResultController::class, 'index'])->middleware('permission:result.view');
});

// ======================= EXAMS (student) =======================
Route::middleware('auth:sanctum')->prefix('student')->group(function () {
    Route::get('/exams/{exam}', [StudentExamController::class, 'show']);
    Route::get('/exams/{exam}/questions', [StudentExamController::class, 'questions']);
    Route::post('/exams/{exam}/start', [StudentExamController::class, 'start']);
    Route::post('/attempts/{attempt}/answers', [StudentExamController::class, 'saveAnswer']);
    Route::post('/attempts/{attempt}/submit', [StudentExamController::class, 'submit']);
    // Route::get('/results', [StudentResultController::class, 'index']); // result.view_own logic, filtered to auth()->user()->student
});
