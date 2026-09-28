<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'healthy',
        'service' => config('app.name', 'Exam Management System API'),
        'timestamp' => now()->toISOString(),
    ]);
});
