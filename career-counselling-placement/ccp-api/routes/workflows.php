<?php

use App\Http\Controllers\AdvisingController as A;
use App\Http\Controllers\CvLifecycleController as C;
use App\Http\Controllers\OpportunityLifecycleController as O;
use App\Http\Controllers\PlacementController as P;
use Illuminate\Support\Facades\Route;

Route::get('advisors', [A::class, 'advisors']);
Route::post('advisor-preferences', [A::class, 'preferences']);
Route::get('allocations', [A::class, 'allocations']);
Route::post('allocations/{student}', [A::class, 'allocate']);
Route::delete('allocations/{student}', [A::class, 'reject']);
Route::get('sessions', [A::class, 'sessions']);
Route::post('sessions', [A::class, 'session']);
Route::patch('sessions/{id}', [A::class, 'session']);
Route::get('plans/{student}', [A::class, 'plan']);
Route::put('plans/{student}', [A::class, 'savePlan']);
Route::patch('milestones/{id}', [A::class, 'milestone']);
Route::get('cvs', [C::class, 'index']);
Route::post('cvs', [C::class, 'create'])->middleware('throttle:upload');
Route::get('cvs/{cv}', [C::class, 'show']);
Route::post('cvs/{cv}/submit', [C::class, 'submit']);
Route::post('cvs/{cv}/review', [C::class, 'review']);
Route::get('cvs/{cv}/download', [C::class, 'download']);
Route::get('opportunities', [O::class, 'index']);
Route::post('opportunities', [O::class, 'create']);
Route::post('opportunities/{opportunity}/review', [O::class, 'review']);
Route::put('opportunities/{opportunity}/save', [O::class, 'save']);
Route::get('opportunities/{opportunity}/eligibility', [P::class, 'eligibility']);
Route::get('applications', [P::class, 'index']);
Route::post('applications', [P::class, 'apply']);
Route::get('applications/{application}', [P::class, 'detail']);
Route::patch('applications/{application}', [P::class, 'change']);
Route::get('interviews', [P::class, 'interviews']);
Route::post('applications/{application}/interview', [P::class, 'interview']);
Route::patch('interviews/{id}', [P::class, 'feedback']);
Route::post('applications/{application}/offer', [P::class, 'offer']);
Route::post('offers/{id}/respond', [P::class, 'respond']);
Route::get('placements', [P::class, 'placements']);

Route::get('students/{student}', [A::class, 'student']);
Route::get('advisor-profile', [A::class, 'advisorProfile']);
Route::put('advisor-profile', [A::class, 'saveAdvisorProfile']);
