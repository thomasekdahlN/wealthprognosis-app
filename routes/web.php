<?php

use App\Http\Controllers\AnalysisDownloadController;
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

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/features', 'features')->name('features');
Route::view('/pricing', 'pricing')->name('pricing');
Route::view('/about', 'about')->name('about');
Route::view('/faq', 'faq')->name('faq');

Route::get('/download/analysis/{file}', [AnalysisDownloadController::class, 'download'])
    ->middleware(['auth', 'signed'])
    ->name('download.analysis');
