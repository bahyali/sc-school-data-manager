<?php

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


Route::get('/all/upload', function () {
    return view('all_upload');
});


Route::get('/active/upload', function () {
    return view('active_upload');
});



Route::post('/import/excel', [App\Http\Controllers\ImporterController::class, 'excelImporting'])->name('excelImporting');


Route::get('/revoked/store', [App\Http\Controllers\ImporterController::class, 'storeRevokedSchools'])->name('storeRevokedSchools');
Route::get('/closed/store', [App\Http\Controllers\ImporterController::class, 'storeClosedSchools'])->name('storeClosedSchools');


