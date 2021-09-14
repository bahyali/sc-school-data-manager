<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/importing', [App\Http\Controllers\ImporterController::class, 'importing'])->name('importing');


Route::get('/schools/{status?}', [App\Http\Controllers\API\SchoolController::class, 'getSchools'])->name('getSchools');
Route::get('/schools-revisions', [App\Http\Controllers\API\SchoolController::class, 'getSchoolsRevisions'])->name('getSchoolsRevisions');


Route::get('/school/{id}', [App\Http\Controllers\API\SchoolController::class, 'getOneSchool'])->name('getOneSchool');
Route::post('/date-school', [App\Http\Controllers\API\SchoolController::class, 'getSchoolByDate'])->name('getSchoolByDate');


// Route::get('/schools/{status}', [App\Http\Controllers\API\SchoolController::class, 'getActiveSchools'])->name('getActiveSchools');
// Route::get('/schools/revoked', [App\Http\Controllers\API\SchoolController::class, 'getRevokedSchools'])->name('getRevokedSchools');
// Route::get('/schools/closed', [App\Http\Controllers\API\SchoolController::class, 'getClosedSchools'])->name('getClosedSchools');


