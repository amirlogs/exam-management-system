<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfirmedQuestionController;
use App\Http\Controllers\FlagQuestionController;
use App\Http\Controllers\ImportQuestionController;
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
    Route::post('/courses/{courseId}/question-bank-imports', [ImportQuestionController::class, 'store']);
    Route::post('/question-bank-imports/{importId}', [ImportQuestionController::class, 'show']);
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

    Route::patch('/questions/{questionId}', [ConfirmedQuestionController::class,  'update']);
    Route::delete('/questions/{questionId}', [ConfirmedQuestionController::class,  'destroy']);
    Route::get('/questions/{questionId}/flags', [ConfirmedQuestionController::class,  'show']);
    Route::get('/questions/{importId}', [ConfirmedQuestionController::class,  'index']);
});
