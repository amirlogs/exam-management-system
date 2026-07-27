<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfirmedQuestionController;
use App\Http\Controllers\FlagQuestionController;
use App\Http\Controllers\ImportQuestionController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuestionTagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/login', [AuthController::class, 'login']);

// authentication needed modules

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {

    // -------------------- Question Bank Import -------------------- //
    Route::post('/courses/{courseId}/question-bank-imports', [ImportQuestionController::class, 'store'])
        ->middleware('permission:upload_question_bank');

    Route::get('/question-bank-imports', [ImportQuestionController::class, 'index'])
        ->middleware('permission:view_question_bank');

    Route::get('/question-bank-imports/{importId}', [ImportQuestionController::class, 'show'])
        ->middleware('permission:view_question_bank');

    Route::patch('/question-bank-imports/{importId}/questions/{rowIndex}', [ImportQuestionController::class, 'update'])
        ->middleware('permission:edit_question_import');

    Route::post('/question-bank-imports/{importId}/confirm', [ImportQuestionController::class, 'confirm'])
        ->middleware('permission:confirm_question_import');

    Route::get('/question-bank-imports/{importId}/flags', [ImportQuestionController::class, 'flags'])
        ->middleware('permission:view_question_bank');

    // -------------------- Flag Question -------------------- //
    Route::post('/questions/{questionId}/flags', [FlagQuestionController::class, 'store'])
        ->middleware('permission:flag_questions');

    Route::patch('/questions/{questionId}/flags/{flagId?}', [FlagQuestionController::class, 'update'])
        ->middleware('permission:flag_questions');

    Route::patch('/question-flags/{flagId}/resolve', [FlagQuestionController::class, 'resolve'])
        ->middleware('permission:resolve_flags');

    // -------------------- Questions CRUD -------------------- //
    Route::get('/courses/{courseId}/available-questions', [QuestionController::class, 'index'])
        ->middleware('permission:view_question_bank');

    Route::post('/courses/{courseId}/questions', [QuestionController::class, 'store'])
        ->middleware('permission:create_questions');

    Route::patch('/questions/{questionId}', [QuestionController::class, 'update'])
        ->middleware('permission:edit_questions');

    Route::post('/questions/{questionId}/confirm', [QuestionController::class, 'confirm'])
        ->middleware('permission:create_questions');

    Route::get('/questions/{questionId}/flags', [ConfirmedQuestionController::class, 'show'])
        ->middleware('permission:view_question_bank');

    Route::get('/questions/{importId}', [ConfirmedQuestionController::class, 'index'])
        ->middleware('permission:view_question_bank');

    Route::delete('/questions/{questionId}', [ConfirmedQuestionController::class, 'destroy'])
        ->middleware('permission:delete_questions');

    // -------------------- Question Tags -------------------- //
    Route::get('/courses/{courseId}/tags', [QuestionTagController::class, 'index'])
        ->middleware('permission:view_question_bank');

    Route::post('/courses/{courseId}/tags', [QuestionTagController::class, 'store'])
        ->middleware('permission:manage_tags');

    Route::delete('/courses/{courseId}/tags', [QuestionTagController::class, 'destroy'])
        ->middleware('permission:manage_tags');
});
