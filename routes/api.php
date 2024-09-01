<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

//public routes 
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

//protected routes
Route::group(['middleware' => ['auth:sanctum']],  function () {
    Route::post('/verify', [AuthController::class, 'verify']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('/tags', TagController::class);
    Route::apiResource('/posts', PostController::class);
    Route::get('/trashed', [PostController::class, 'trashed']);
    Route::patch('/restore/{id}', [PostController::class, 'restore']);
    Route::delete('/force/{id}', [PostController::class, 'forceDelete']);
});
