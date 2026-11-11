<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/public/overview', [PublicController::class, 'index'])->middleware('throttle:api');
Route::get('/public/settings', [PublicController::class, 'settings'])->middleware('throttle:api');
Route::get('/public/site-logo', [PublicController::class, 'logo'])->middleware('throttle:api');
Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
    Route::get('/v1/avatar', [AvatarController::class, 'show']);
    Route::post('/v1/avatar', [AvatarController::class, 'save'])->middleware('throttle:upload');
    Route::delete('/v1/avatar', [AvatarController::class, 'delete']);
});
Route::prefix('v1')->middleware(['auth:sanctum', 'active', 'approved', 'password.changed', 'email.required', 'throttle:api'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'read']);
    Route::post('/notifications/{notice}/read', [NotificationController::class, 'read'])->whereUuid('notice');
    Route::get('/reminders', [NotificationController::class, 'reminders']);
    Route::post('/reminders', [NotificationController::class, 'saveReminder']);
    Route::delete('/reminders/{id}', [NotificationController::class, 'deleteReminder']);
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::patch('/admin/users/{user}', [AdminController::class, 'update']);
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroy']);
    Route::get('/admin/settings', [AdminController::class, 'settings']);
    Route::put('/admin/settings', [AdminController::class, 'saveSettings']);
    Route::post('/admin/settings/logo', [AdminController::class, 'saveLogo'])->middleware('throttle:upload');
    Route::delete('/admin/settings/logo', [AdminController::class, 'deleteLogo']);
    Route::get('/admin/audit', [AdminController::class, 'audits']);
    Route::get('/admin/reports', [AdminController::class, 'reports']);
    Route::get('/admin/reports.csv', [AdminController::class, 'csv']);
    Route::post('/admin/companies', [AdminController::class, 'company']);
});
