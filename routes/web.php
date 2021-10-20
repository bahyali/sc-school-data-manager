<?php

use App\Models\DataSource;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/gui/upload', function () {
    $excel_data_sources = DataSource::where('resource', 'excel')->pluck('name');
    return view('all_upload', ['data_sources' => $excel_data_sources]);
});


// Route::get('/active/upload', function () {
//     return view('active_upload');
// });

Route::get('/ontario/upload', [App\Http\Controllers\ImporterController::class, 'ontarioImporting'])->name('ontarioImporting');


Route::post('/import/excel', [App\Http\Controllers\ImporterController::class, 'excelImporting'])->name('excelImporting');

Route::get('/crawl/revoked', [App\Http\Controllers\ImporterController::class, 'storeRevokedSchools'])->name('storeRevokedSchools');

Route::get('/crawl/closed', [App\Http\Controllers\ImporterController::class, 'storeClosedSchools'])->name('storeClosedSchools');

Route::get('/remix/all', [App\Http\Controllers\ImporterController::class, 'remixAllSchools'])->name('remixAllSchools');



Route::get('/conflict/{school_id}/{column?}', [App\Http\Controllers\ImporterController::class, 'getConflicts'])->name('getConflicts');


