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
    return redirect('home');
});

Auth::routes(['register' => false]);


// Route::group(['middleware' => ['auth']], function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // Route::resource('users', App\Http\Controllers\UserController::class);
// });


Route::get('/run-drilling', [\App\Http\Controllers\MainExtractorController::class, 'runDrilling']);

Route::get('/run-workover', [\App\Http\Controllers\MainExtractorController::class, 'runWorkover']);


Route::get('extractorInterfaces', function () {
    return redirect('extractorInterfaces/create');
});
Route::resource('extractorInterfaces', App\Http\Controllers\ExtractorInterfaceController::class)->only(['create', 'store']);

Route::post('validate-report-data', [\App\Http\Controllers\ExtractorInterfaceController::class, 'validateReportData'])->name('extractorInterfaces.validateReportData');
Route::get('download-single/{name}', [\App\Http\Controllers\ExtractorInterfaceController::class, 'downloadSingle'])->name('extractorInterfaces.downloadSingle');
Route::post('empty-ddrdwr-files', [\App\Http\Controllers\ExtractorInterfaceController::class, 'emptyDDRDWRFiles'])->name('extractorInterfaces.emptyDDRDWRFiles');




Route::get('download-single2/{name}', [\App\Http\Controllers\ExtractorInterfaceController::class, 'downloadSingle2'])->name('extractorInterfaces.downloadSingle2');
