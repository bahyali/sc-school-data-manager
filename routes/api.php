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


Route::post('/importing', [App\Http\Controllers\ImporterController::class, 'excelImporting'])->name('importing');

Route::apiResources([
    'data-sources' => App\Http\Controllers\API\DataSourceController::class
]);

Route::get('/schools/{status?}', [App\Http\Controllers\API\SchoolController::class, 'getSchools'])
    ->name('getSchools');

Route::get('/schools-revisions', [App\Http\Controllers\API\SchoolController::class, 'getSchoolsRevisions'])
    ->name('getSchoolsRevisions');

Route::post('/crawl/{id}', [App\Http\Controllers\ImporterController::class, 'crawlSchoolById'])
    ->name('crawlSchoolById');

Route::post('/update/active_schools', [App\Http\Controllers\ImporterController::class, 'ontarioImporting'])
    ->name('crawl_active_schools');

Route::get('/school/{id}', [App\Http\Controllers\API\SchoolController::class, 'getOneSchool'])
    ->name('getOneSchool');

Route::get('/date-school', [App\Http\Controllers\API\SchoolController::class, 'getSchoolByDate'])
    ->name('getSchoolByDate');




Route::get('/conflicts', [App\Http\Controllers\API\SchoolController::class, 'getConflictedSchools']);
Route::get('/school-conflicts/{id}/{column?}', [App\Http\Controllers\API\SchoolController::class, 'getSchoolConflictColumns']);
Route::post('/fix-conflict', [App\Http\Controllers\API\SchoolController::class, 'FixConflict']);

Route::get('/repeated-schools', [App\Http\Controllers\API\SchoolController::class, 'getAllRepeatedSchools']);
Route::get('/repeated-schools/{school_name}', [App\Http\Controllers\API\SchoolController::class, 'getOneRepeatedSchool']);


