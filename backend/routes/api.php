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

});

//
//
//
//
//
//
//
//
// Route::middleware('permmision:admin')->group(function () {
//     Route::get('/admin', Admincontroller::class, 'index');
//     Route::get('/admin/create', Admincontroller::class, 'create');
//     Route::post('/admin/store', Admincontroller::class, 'store');
//     Route::get('/admin/edit/{id}', Admincontroller::class, 'edit');
//     Route::post('/admin/update/{id}', Admincontroller::class, 'update');
// });

// Route::get('/admin', Admincontroller::class, 'index')->middleware('permmision:viewadmin');
// Route::get('/admin/create', Admincontroller::class, 'create')->middleware('permmision:createadmin');
// Route::post('/admin/store', Admincontroller::class, 'store')->middleware('permmision:createadmin');
// Route::get('/admin/edit/{id}', Admincontroller::class, 'edit')->middleware('permmision:editadmin');
// Route::post('/admin/update/{id}', Admincontroller::class, 'update')->middleware('permmision:editadmin');
