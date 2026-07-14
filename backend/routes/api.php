<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Routing\Loader\Configurator\Routes;

Route::get("/user", function (Request $request) {
    return $request->user();
})->middleware("auth:sanctum");

Route::post("/auth/login", [AuthController::class, "login"]);

// authentication needed modules

Route::middleware("auth:sanctum")->prefix("auth")->group(function () {
    Route::post("/change-password", [AuthController::class, "changePassword"]);
    Route::get("/me", [AuthController::class, "me"]);
    Route::post("/logout", [AuthController::class, "logout"]);
});
