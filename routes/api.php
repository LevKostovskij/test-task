<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::apiResource('tasks', TaskController::class)->only(['index', 'store', 'update', 'destroy']);
    
    Route::post('/tasks/{task}/reminder', [TaskController::class, 'setReminder']);
    Route::delete('/tasks/{task}/reminder', [TaskController::class, 'deleteReminder']);
});
