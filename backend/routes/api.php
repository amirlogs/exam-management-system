<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\ConfirmedQuestionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FlagQuestionController;
use App\Http\Controllers\ImportQuestionController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\UniversityController;
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
    Route::get('/universities/{Id}', [UniversityController::class, 'show']);
    Route::patch('/universities/{Id}', [UniversityController::class, 'update'])->middleware('permission:university.update');
    Route::delete('/universities/{Id}', [UniversityController::class, 'destroy'])->middleware('permission:university.archive');
    Route::post('/universities/{Id}/restore', [UniversityController::class, 'restore'])->middleware('permission:university.archive');

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

    Route::post('/curriculums', [CurriculumController::class, 'store'])->middleware('permission:curriculum.create');

});
//
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

// Usage in routes/api.php
// Route::post('/exams/{exam}/approve', [ExamController::class, 'approve'])
// ->middleware('permission:exam.approve');
