<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CvLifecycleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\OpportunityLifecycleController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentVerificationController;
use Illuminate\Support\Facades\Route;

foreach (['id', 'student', 'cv', 'opportunity', 'application', 'user', 'company'] as $parameter) {
    Route::pattern($parameter, '[0-9]+');
}

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth-mail');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:auth-mail');
Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:auth-mail');
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verification-notification', [AuthController::class, 'resend'])->middleware('throttle:auth-mail');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->middleware(['signed', 'throttle:auth-mail'])->name('verification.verify');
    Route::post('/change-password', [AuthController::class, 'changePassword']);
});
Route::prefix('v1')->middleware(['auth:sanctum', 'active', 'approved', 'email.required', 'password.changed', 'throttle:api'])->group(function () {
    require __DIR__.'/workflows.php';
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile/steps/{step}', [ProfileController::class, 'update'])->whereNumber('step');
    Route::get('/student-verifications', [StudentVerificationController::class, 'index']);
    Route::get('/student-verifications/{user}', [StudentVerificationController::class, 'show']);
    Route::patch('/student-verifications/{user}', [StudentVerificationController::class, 'update']);
});
Route::middleware(['auth:sanctum', 'active', 'approved', 'email.required', 'password.changed', 'throttle:api'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show']);
    Route::get('/opportunities', [OpportunityLifecycleController::class, 'index']);
    Route::get('/cvs/{cv}/download', [CvLifecycleController::class, 'download']);

    Route::middleware('role:student,advisor')->group(function () {
        Route::get('/notes', [NoteController::class, 'index']);
        Route::post('/notes', [NoteController::class, 'store']);
    });
    Route::middleware('role:advisor')->group(function () {
        Route::get('/students', [PeopleController::class, 'students']);
    });
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [PeopleController::class, 'users']);
        Route::post('/users', [PeopleController::class, 'createUser']);
        Route::get('/companies', [PeopleController::class, 'companies']);
        Route::patch('/companies/{company}/verify', [PeopleController::class, 'verify']);
    });
});

require __DIR__.'/platform.php';
