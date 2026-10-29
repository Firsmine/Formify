<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    Route::middleware('auth:sanctum')->group(function () {
        // form
        Route::post('forms', [FormController::class, 'store']);
        Route::get('forms', [FormController::class, 'index']);
        Route::get('forms/{form_slug}', [FormController::class, 'show']);

        // question
        Route::post('forms/{form_slug}/questions', [QuestionController::class, 'store']);
        Route::delete('forms/{form_slug}/questions/{question_id}', [QuestionController::class, 'destroy']);

        // response
        Route::post('forms/{form_slug}/responses', [ResponseController::class, 'store']);
        Route::get('forms/{form_slug}/responses', [ResponseController::class, 'index']);
    });
});
