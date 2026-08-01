<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\ConfirmedQuestionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FlagQuestionController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ImportQuestionController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\UserController;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ------------------ AUTH -------------------------
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// ------------------ University Management -------------------------
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/universities', [UniversityController::class, 'store'])->middleware('permission:university.create');
    Route::get('/universities', [UniversityController::class, 'index']);
    Route::get('/universities/{university}', [UniversityController::class, 'show']);
    Route::patch('/universities/{university}', [UniversityController::class, 'update'])->middleware('permission:university.update');
    Route::delete('/universities/{university}', [UniversityController::class, 'destroy'])->middleware('permission:university.archive');
    Route::post('/universities/{university}/restore', [UniversityController::class, 'restore'])->middleware('permission:university.archive');

    // ------------------ Collge|Department|Program|Course -------------------------
    Route::post('/colleges', [CollegeController::class, 'store'])->middleware('permission:college.create');
    Route::get('/colleges', [CollegeController::class, 'index']);
    Route::patch('/colleges/{college}', [CollegeController::class, 'update'])->middleware('permission:college.update');
    Route::delete('/colleges/{college}', [CollegeController::class, 'destroy'])->middleware('permission:college.archive');
    Route::post('/colleges/{college}/restore', [CollegeController::class, 'restore'])->middleware('permission:college.archive');

    Route::post('/departments', [DepartmentController::class, 'store'])->middleware('permission:department.create');
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->middleware('permission:department.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:department.archive');
    Route::post('/departments/{department}/restore', [DepartmentController::class, 'restore'])->middleware('permission:department.archive');

    Route::post('/programs', [ProgramController::class, 'store'])->middleware('permission:program.create');
    Route::get('/programs', [ProgramController::class, 'index']);
    Route::patch('/programs/{program}', [ProgramController::class, 'update'])->middleware('permission:program.update');
    Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])->middleware('permission:program.archive');
    Route::post('/programs/{program}/restore', [ProgramController::class, 'restore'])->middleware('permission:program.archive');

    Route::post('/courses', [CourseController::class, 'store'])->middleware('permission:course.create');
    Route::get('/courses', [CourseController::class, 'index']);
    Route::patch('/courses/{course}', [CourseController::class, 'update'])->middleware('permission:course.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->middleware('permission:course.update');
    Route::post('/courses/{course}/restore', [CourseController::class, 'restore'])->middleware('permission:course.update');

    // -------------------------- Role, Permission and Assignment --------------------------//
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:role.create');
    Route::get('/roles', [RoleController::class, 'index']);
    Route::patch('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:role.update');
    Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermission'])->middleware('permission:permission.assign');
    Route::post('/roles/{role}/permissions/remove', [RoleController::class, 'removePermission'])->middleware('permission:permission.assign');
    Route::delete('/role/{role}', [RoleController::class, 'destroy'])->middleware('permission:role.delete');
    Route::post('/role/{role}/restore', [RoleController::class, 'restore'])->middleware('permission:role.delete');

    Route::post('/users', [UserController::class, 'store'])->middleware('permission:user.create');
    Route::get('/users', [UserController::class, 'index']);
    Route::patch('/users/{user}', [UserController::class, 'update'])->middleware('permission:user.update');
    Route::post('/users/{user}/roles', [UserController::class, 'assignRole'])->middleware('permission:role.assign');
    Route::delete('/user/{user}/roles/{userRole}', [UserController::class, 'removeRole'])->middleware('permission:role.remove');

    Route::post('/curriculums', [CurriculumController::class, 'store'])->middleware('permission:curriculum.create');
    Route::get('/curriculums', [CurriculumController::class, 'index']);
    Route::patch('/curriculums/{curriculum}', [CurriculumController::class, 'update']); // ->middleware('permission:curriculum.update');
    Route::post('/curriculums/{curriculum}/activate', [CurriculumController::class, 'activate']); // ->middleware('permission:curriculum.activate')
    Route::post('/curriculums/{curriculum}/deactivate', [CurriculumController::class, 'deactivate']); // ->middleware('permission:curriculum.deactivate')
    Route::post('/curriculums/{curriculum}/courses', [CurriculumController::class, 'addCourse']); // ->middleware(['permission:curriculum_course.add']);
    Route::patch('/curriculums/courses/{curriculumCourse}', [CurriculumController::class, 'updateCourse']); // ->middleware('permission:curriculum_course.update');
    Route::delete('/curriculums/courses/{curriculumCourse}', [CurriculumController::class, 'removeCourse']); // ->middleware('permission:curriculum_course.remove');

    // ------------------------------------Semester and Section---------------------------------------//
    Route::post('/semesters', [SemesterController::class, 'store']); // ->middleware('permission:semester.create');
    Route::get('/semesters', [SemesterController::class, 'index']);
    Route::patch('/semesters/{semester}', [SemesterController::class, 'update']); // -> middleware('permission:semester.update');
    Route::post('/semesters/{semester}/open', [SemesterController::class, 'open']); // -> middleware('permission:semester.update');
    Route::post('/semesters/{semester}/close', [SemesterController::class, 'close']); // -> middleware('permission:semester.update');
    Route::delete('/semesters/{semester}/archive', [SemesterController::class, 'archive']); // -> middleware('permission:semester.archive');
    Route::post('/semesters/{semester}/restore', [SemesterController::class, 'restore']); // -> middleware('permission:semester.archive');

    Route::post('/sections', [SectionController::class, 'store']); // ->middleware('permission:section.create')
    Route::get('/sections', [SectionController::class, 'index']);

    // ------------------------ IMPORT student|instructor|section|classes ------------------------
    Route::post('/imports/students', [ImportController::class, 'importStudents'])->middleware('permission:student.create');

});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/courses/{courseId}/question-bank-imports', [ImportQuestionController::class, 'store']);
    Route::get('/question-bank-imports/{importId}', [ImportQuestionController::class, 'show']);
    Route::get('/question-bank-imports', [ImportQuestionController::class, 'index']);
    Route::patch('/question-bank-imports/{importId}/questions/{rowIndex}', [ImportQuestionController::class, 'update']);
    Route::post('/question-bank-imports/{importId}/confirm', [ImportQuestionController::class, 'confirm']);
    Route::post('/question-bank-imports/{importId}/approve', [ImportQuestionController::class, 'approve']);
    Route::get('/question-bank-imports/{importId}/falg', [ImportQuestionController::class, 'flags']);

    // --------------------------Flag Question ------------------------------ //
    Route::post('/questions/{questionId}/flags', [FlagQuestionController::class,  'store']);
    Route::get('/question-bank-imports/{importId}/flags', [FlagQuestionController::class,  'index']);
    Route::get('/questions/{questionId}/flags', [FlagQuestionController::class,  'index']);
    Route::patch('/questions/{questionId}/flags/{flagId?}', [FlagQuestionController::class,  'update']);
    Route::patch('/question-flags/{flagId}/resolve', [FlagQuestionController::class,  'resolve']);

    // --------------------------   Questions crud ------------------------------ //
    Route::patch('/questions/{questionId}', [ConfirmedQuestionController::class,  'update']);
    Route::delete('/questions/{questionId}', [ConfirmedQuestionController::class,  'destroy']);
    Route::get('/questions/{questionId}/flags', [ConfirmedQuestionController::class,  'show']);
    Route::get('/questions/{importId}', [ConfirmedQuestionController::class,  'index']);

    // ----------------------
    Route::get('/cources/{courseId}/available-questions', [QuestionController::class, 'index']); // not implemented
    Route::post('/cources/{courseId}/questions', [QuestionController::class, 'store']);
    Route::patch('/questions/{questionId}', [QuestionController::class, 'update']);
    Route::post('/questions/{questionId}/confirm', [QuestionController::class, 'confirm']);

    // ----------------------------- Grouping Questions ---------------------------//
});
