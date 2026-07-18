<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\QuestionBankImportController;
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

Route::middleware('auth:sanctum')->prefix('courses')->group(function () {
    Route::post('/{courseId}/question-bank-imports', [QuestionBankImportController::class, 'store']);
    Route::post('/question-bank-imports/{importId}', [QuestionBankImportController::class, 'show']);
    Route::patch('/question-bank-imports/{importId}/questions/{rowIndex}', [QuestionBankImportController::class, 'update']);
    Route::post('/question-bank-imports/{importId}/confirm', [QuestionBankImportController::class, 'confirm']);
    Route::post('/question-bank-imports/{importId}/approve', [QuestionBankImportController::class, 'approve']);
});
