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
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuestionFlagController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StudentController;
// use App\Http\Controllers\Student\StudentResultController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\TeachingController;
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
    Route::get('/universities', [UniversityController::class, 'index'])->middleware('permission:university.view');
    Route::patch('/universities/{university}', [UniversityController::class, 'update'])->middleware('permission:university.update');
    // Route::get('/universities/archived', [UniversityController::class, 'archived'])->middleware('permission:university.view');
    // Route::get('/universities/{university}', [UniversityController::class, 'show'])->middleware('permission:university.view');
    // Route::delete('/universities/{university}', [UniversityController::class, 'destroy'])->middleware('permission:university.archive');
    // Route::post('/universities/{university}/restore', [UniversityController::class, 'restore'])->middleware('permission:university.restore');

    Route::post('/colleges', [CollegeController::class, 'store'])->middleware('permission:college.create');
    Route::get('/colleges', [CollegeController::class, 'index'])->middleware('permission:college.view');
    Route::get('/colleges/archived', [CollegeController::class, 'archived'])->middleware('permission:college.view');
    Route::get('/colleges/{college}', [CollegeController::class, 'show'])->middleware('permission:college.view');
    Route::patch('/colleges/{college}', [CollegeController::class, 'update'])->middleware('permission:college.update');
    Route::delete('/colleges/{college}', [CollegeController::class, 'destroy'])->middleware('permission:college.archive');
    Route::post('/colleges/{college}/restore', [CollegeController::class, 'restore'])->middleware('permission:college.restore');

    Route::post('/departments', [DepartmentController::class, 'store'])->middleware('permission:department.create');
    Route::get('/departments', [DepartmentController::class, 'index'])->middleware('permission:department.view');
    Route::get('/departments/archived', [DepartmentController::class, 'archived'])->middleware('permission:department.view');
    Route::get('/departments/{department}', [DepartmentController::class, 'show'])->middleware('permission:department.view');
    Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->middleware('permission:department.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:department.archive');
    Route::post('/departments/{department}/restore', [DepartmentController::class, 'restore'])->middleware('permission:department.restore');

    Route::post('/programs', [ProgramController::class, 'store'])->middleware('permission:program.create');
    Route::get('/programs', [ProgramController::class, 'index'])->middleware('permission:program.view');
    Route::get('/programs/archived', [ProgramController::class, 'archived'])->middleware('permission:program.view');
    Route::patch('/programs/{program}', [ProgramController::class, 'update'])->middleware('permission:program.update');
    Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])->middleware('permission:program.archive');
    Route::post('/programs/{program}/restore', [ProgramController::class, 'restore'])->middleware('permission:program.restore');

    Route::post('/courses', [CourseController::class, 'store'])->middleware('permission:course.create');
    Route::get('/courses', [CourseController::class, 'index'])->middleware('permission:course.view');
    Route::get('/courses/archived', [CourseController::class, 'archived'])->middleware('permission:course.view');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->middleware('permission:course.view');
    Route::patch('/courses/{course}', [CourseController::class, 'update'])->middleware('permission:course.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->middleware('permission:course.archive');
    Route::post('/courses/{course}/restore', [CourseController::class, 'restore'])->middleware('permission:course.restore');

    // =================== Roles || Permissions || Users ==========
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:role.create');
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:role.view');
    Route::get('/roles/archived', [RoleController::class, 'archived'])->middleware('permission:role.view');
    Route::patch('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:role.update');
    Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermission'])->middleware('permission:permission.assign');
    Route::post('/roles/{role}/permissions/remove', [RoleController::class, 'removePermission'])->middleware('permission:permission.remove');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:role.archive');
    Route::post('/roles/{role}/restore', [RoleController::class, 'restore'])->middleware('permission:role.restore');

    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permission.view');

    Route::post('/users', [UserController::class, 'store'])->middleware('permission:user.create');
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:user.view');
    Route::patch('/users/{user}', [UserController::class, 'update'])->middleware('permission:user.update');
    Route::post('/users/{user}/roles', [UserController::class, 'assignRole'])->middleware('permission:role.assign');
    Route::delete('/users/{user}/roles', [UserController::class, 'removeRole'])->middleware('permission:role.remove');
    Route::post('/users/{user}/disable', [UserController::class, 'disable'])->middleware('permission:user.disable');
    Route::post('/users/{user}/activate', [UserController::class, 'activate']); // ->middleware('permission:user.activate');

    Route::get('/me/allowed-routes', [UserController::class, 'allowedRoutes']);
    Route::post('/me/workspace', [UserController::class, 'setWorkspace']);


    // =================== CURRICULUM ===================
    Route::post('/curriculums', [CurriculumController::class, 'store'])->middleware('permission:curriculum.create');
    Route::get('/curriculums', [CurriculumController::class, 'index'])->middleware('permission:curriculum.view');
    Route::patch('/curriculums/{curriculum}', [CurriculumController::class, 'update'])->middleware('permission:curriculum.update');
    Route::post('/curriculums/{curriculum}/activate', [CurriculumController::class, 'activate'])->middleware('permission:curriculum.activate');
    Route::post('/curriculums/{curriculum}/deactivate', [CurriculumController::class, 'deactivate'])->middleware('permission:curriculum.deactivate');
    Route::post('/curriculums/{curriculum}/courses', [CurriculumController::class, 'addCourse'])->middleware('permission:curriculum.course.add');
    Route::patch('/curriculums/courses/{curriculumCourse}', [CurriculumController::class, 'updateCourse'])->middleware('permission:curriculum.course.update');
    Route::delete('/curriculums/courses/{curriculumCourse}', [CurriculumController::class, 'removeCourse'])->middleware('permission:curriculum.course.remove');

    // ==================== SEMESTERS | SECTIONS ====================
    Route::post('/semesters', [SemesterController::class, 'store'])->middleware('permission:semester.create');
    Route::get('/semesters', [SemesterController::class, 'index'])->middleware('permission:semester.view');
    Route::get('/semesters/archived', [SemesterController::class, 'archived'])->middleware('permission:semester.view');
    Route::patch('/semesters/{semester}', [SemesterController::class, 'update'])->middleware('permission:semester.update');
    Route::post('/semesters/{semester}/open', [SemesterController::class, 'open'])->middleware('permission:semester.open');
    Route::post('/semesters/{semester}/close', [SemesterController::class, 'close'])->middleware('permission:semester.close');
    Route::delete('/semesters/{semester}', [SemesterController::class, 'archive'])->middleware('permission:semester.archive');
    Route::post('/semesters/{semester}/restore', [SemesterController::class, 'restore'])->middleware('permission:semester.restore');

    Route::post('/sections', [SectionController::class, 'store'])->middleware('permission:section.create');
    Route::get('/sections', [SectionController::class, 'index'])->middleware('permission:section.view');
    Route::get('/sections/archived', [SectionController::class, 'archived'])->middleware('permission:section.view');
    Route::patch('/sections/{section}', [SectionController::class, 'update'])->middleware('permission:section.update');
    Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->middleware('permission:section.archive');
    Route::post('/sections/{section}/restore', [SectionController::class, 'restore'])->middleware('permission:section.restore');

    // ===================== IMPORT (students, sections, instructors, users, questions)
    Route::get('/imports', [ImportController::class, 'index'])->middleware('permission:import.view');
    Route::post('/imports/{type}', [ImportController::class, 'store'])->middleware('import.permission');
    Route::get('/imports/{importHistory}', [ImportController::class, 'show'])->middleware('import.permission');
    Route::patch('/imports/{importHistory}/rows/{rowNumber}', [ImportController::class, 'update'])->middleware('import.permission');
    Route::post('/imports/{importHistory}/confirm', [ImportController::class, 'confirm'])->middleware('import.permission');
    Route::delete('/imports/{importHistory}', [ImportController::class, 'cancel'])->middleware('import.permission');
    Route::delete('/imports/{importHistory}/rows/{rowNumber}', [ImportController::class, 'destroy'])->middleware('import.permission');

    // ======================= COURSE OFFERING =====================
    Route::post('/course-offerings/suggestions/generate', [CourseOfferingController::class, 'generateSuggestions'])->middleware('permission:course_offering.create');
    Route::post('/course-offerings', [CourseOfferingController::class, 'store'])->middleware('permission:course_offering.create');
    Route::get('/course-offerings', [CourseOfferingController::class, 'index'])->middleware('permission:course_offering.view');
    Route::get('/course-offerings/archived', [CourseOfferingController::class, 'archived'])->middleware('permission:course_offering.view');
    Route::get('/course-offerings/{courseOffering}', [CourseOfferingController::class, 'show'])->middleware('permission:course_offering.view');
    Route::patch('/course-offerings/{courseOffering}', [CourseOfferingController::class, 'update'])->middleware('permission:course_offering.update');
    Route::post('/course-offerings/{courseOffering}/reopen', [CourseOfferingController::class, 'reopen'])->middleware('permission:course_offering.update');
    Route::post('/course-offerings/{courseOffering}/instructors', [CourseOfferingController::class, 'attachInstructor'])->middleware('permission:course_offering.assign_instructor');
    Route::patch('/course-offerings/{courseOffering}/instructor-assignments/{assignment}', [CourseOfferingController::class, 'updateInstructorAssignment'])->middleware('permission:course_offering.change_instructor');
    Route::delete('/course-offerings/{courseOffering}/instructor-assignments/{assignment}', [CourseOfferingController::class, 'removeInstructorAssignment'])->middleware('permission:course_offering.remove_instructor');
    Route::post('/course-offerings/{courseOffering}/approve', [CourseOfferingController::class, 'approve'])->middleware('permission:course_offering.approve');
    Route::post('/course-offerings/{courseOffering}/reject', [CourseOfferingController::class, 'reject'])->middleware('permission:course_offering.reject');
    Route::post('/course-offerings/{courseOffering}/cancel', [CourseOfferingController::class, 'cancel'])->middleware('permission:course_offering.cancel');
    Route::delete('/course-offerings/{courseOffering}', [CourseOfferingController::class, 'destroy'])->middleware('permission:course_offering.archive');
    Route::post('/course-offerings/{courseOffering}/restore', [CourseOfferingController::class, 'restore'])->middleware('permission:course_offering.restore');

    // ============================ ENROLLMENTS ============================
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->middleware('permission:enrollment.create');
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->middleware('permission:enrollment.view');
    Route::patch('/enrollments/{enrollment}', [EnrollmentController::class, 'update'])->middleware('permission:enrollment.update');

    // ========================== QUESTION BANK ===================
    Route::post('/courses/{course}/questions', [QuestionController::class, 'store'])->middleware('permission:question.create');
    Route::get('/questions', [QuestionController::class, 'index'])->middleware('permission:question.view');
    Route::get('/questions/archived', [QuestionController::class, 'archived'])->middleware('permission:question.view');
    Route::get('/questions/archived/{question}', [QuestionController::class, 'showArchived'])->middleware('permission:question.view');
    Route::get('/questions/{question}', [QuestionController::class, 'show'])->middleware('permission:question.view');
    Route::patch('/questions/{question}', [QuestionController::class, 'update'])->middleware('permission:question.update');
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->middleware('permission:question.archive');
    Route::post('/questions/{question}/restore', [QuestionController::class, 'restore'])->middleware('permission:question.restore');

    //========================== Teachers ========================
    Route::get('/instructors', [InstructorController::class, 'index']); // ->middleware('permission:instructor.view');
    Route::get('/me/teaching', [TeachingController::class, 'index']) ->middleware('permission:course_offering.view');
    Route::get('/me/teaching/{courseOffering}', [TeachingController::class, 'show']) ->middleware('permission:course_offering.view');

    //========================== Students ========================
    Route::get('/students', [StudentController::class, 'index'])->middleware('permission:student.view');

    // ======================== EXAMS(admin/instructor side) ========================
    Route::get('/course-offerings/{courseOffering}/exams', [ExamController::class, 'index'])->middleware('permission:exam.view');
    Route::get('/exams/{exam}', [ExamController::class, 'show'])->middleware('permission:exam.view');
    Route::post('/course-offerings/{courseOffering}/exams', [ExamController::class, 'store'])->middleware('permission:exam.create');
    Route::patch('/exams/{exam}/composition', [ExamController::class, 'composition'])->middleware('permission:exam.update');
    Route::post('/exams/{exam}/questions', [ExamController::class, 'addQuestions'])->middleware('permission:exam.update');
    Route::post('/exams/{exam}/questions/bulk', [ExamController::class, 'addQuestionsBulk'])->middleware('permission:exam.update');
    Route::get('/exams/{exam}/questions', [ExamController::class, 'questions'])->middleware('permission:exam.view');
    Route::delete('/exams/{exam}/questions/{examQuestion}', [ExamController::class, 'removeQuestion'])->middleware('permission:exam.update');
    Route::post('/exams/{exam}/submit-approval', [ExamController::class, 'submitApproval'])->middleware('permission:exam.submit');
    Route::post('/exams/{exam}/revert-to-draft', [ExamController::class, 'revertToDraft'])->middleware('permission:exam.revert');
    Route::post('/exams/{exam}/approve', [ExamController::class, 'approve'])->middleware('permission:exam.approve');
    Route::post('/exams/{exam}/reject', [ExamController::class, 'reject'])->middleware('permission:exam.reject');
    Route::post('/exams/{exam}/schedule', [ExamController::class, 'schedule'])->middleware('permission:exam.schedule');
    Route::patch('/exams/{exam}/schedule', [ExamController::class, 'updateSchedule'])->middleware('permission:exam.schedule.update');
    Route::post('/exams/{exam}/extend-time', [ExamController::class, 'extendTime'])->middleware('permission:exam.extend');
    Route::post('/exams/{exam}/activate', [ExamController::class, 'activate'])->middleware('permission:exam.activate');
    Route::post('/exams/{exam}/publish', [ExamController::class, 'publish'])->middleware('permission:exam.publish'); // admin publish
    Route::post('/exams/{exam}/end', [ExamController::class, 'end'])->middleware('permission:exam.end');
    Route::post('/exams/{exam}/cancel', [ExamController::class, 'cancel'])->middleware('permission:exam.cancel');
    Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->middleware('permission:exam.archive');

    // ====================== GRADING =============================
    Route::post('/exams/{exam}/auto-grade', [GradingController::class, 'autoGrade'])->middleware('permission:grade.autograde');
    Route::patch('/answers/{answer}/grade', [GradingController::class, 'gradeAnswer'])->middleware('permission:grade.update');
    Route::post('/course-offerings/{courseOffering}/grades/submit-verification', [GradingController::class, 'submitVerification'])->middleware('permission:grade.submit');
    Route::post('/grades/{grade}/verify', [GradingController::class, 'verify'])->middleware('permission:grade.verify');
    Route::post('/grades/publish', [GradingController::class, 'publish'])->middleware('permission:grade.publish');


    // ======================== QUESTION FLAGS ========================
    Route::post('/questions/{question}/flags', [QuestionFlagController::class, 'store'])->middleware('permission:question.flag');
    Route::get('/questions/{question}/flags', [QuestionFlagController::class, 'index'])->middleware('permission:question.view');
    Route::patch('/question-flags/{flag}/resolve', [QuestionFlagController::class, 'resolve'])->middleware('permission:question.update');

    // ====================== RESULTS =============================
    // Route::post('/results/generate', [ResultController::class, 'generate'])->middleware('permission:result.generate');
    // Route::post('/results/publish', [ResultController::class, 'publish'])->middleware('permission:result.publish');
    // Route::get('/results', [ResultController::class, 'index'])->middleware('permission:result.view');

    // ============================Users ==============================
});

// ======================= EXAMS (student) =======================
Route::middleware('auth:sanctum')->prefix('student')->group(function () {
    Route::get('/exams', [StudentExamController::class, 'index']);
    Route::get('/exams/{exam}', [StudentExamController::class, 'show']);
    Route::get('/exams/{exam}/questions', [StudentExamController::class, 'questions']);
    Route::post('/exams/{exam}/start', [StudentExamController::class, 'start']);
    Route::post('/attempts/{attempt}/answers', [StudentExamController::class, 'saveAnswer']);
    Route::post('/attempts/{attempt}/submit', [StudentExamController::class, 'submit']);
    // Route::get('/results', [StudentResultController::class, 'index']); // result.view_own logic, filtered to auth()->user()->student
});
