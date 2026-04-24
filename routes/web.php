<?php

use App\Http\Controllers\AnalysisDownloadController;
use App\Http\Controllers\InvitationAcceptController;
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
Route::view('/use-cases', 'use-cases')->name('use-cases');
Route::view('/glossary', 'glossary')->name('glossary');
Route::view('/methodology', 'methodology')->name('methodology');
Route::view('/legal', 'legal')->name('legal');
Route::view('/personvern', 'personvern')->name('personvern');

Route::get('/download/analysis/{file}', [AnalysisDownloadController::class, 'download'])
    ->middleware(['auth', 'signed'])
    ->name('download.analysis');

Route::get('/invitations/{token}', InvitationAcceptController::class)
    ->name('invitations.accept');
