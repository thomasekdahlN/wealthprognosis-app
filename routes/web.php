<?php

declare(strict_types=1);

use App\Http\Controllers\AnalysisDownloadController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing site (Markdown-driven, locale-prefixed)
|--------------------------------------------------------------------------
|
| Each page is sourced from `resources/content/{locale}/{slug}.md` and
| rendered through PublicPageController::show. Route names match the slug
| (with `home` for the locale root) and pick up the active locale via
| URL::defaults so existing `route('features')` calls keep working.
|
*/

/** @var array<int, string> $publicSlugs */
$publicSlugs = ['features', 'pricing', 'about', 'faq', 'use-cases', 'glossary', 'methodology', 'legal'];

Route::get('/', fn () => redirect('/'.(string) config('public_pages.default_locale', 'en'), 301));

Route::middleware('set.locale')
    ->prefix('{locale}')
    ->where(['locale' => 'en|nb'])
    ->group(function () use ($publicSlugs): void {
        Route::get('/', fn (string $locale) => app(PublicPageController::class)->show($locale, 'home'))
            ->name('home');

        foreach ($publicSlugs as $slug) {
            Route::get('/'.$slug, fn (string $locale) => app(PublicPageController::class)->show($locale, $slug))
                ->name($slug);
        }
    });

Route::get('/locale/{locale}', [PublicPageController::class, 'switchLocale'])
    ->where('locale', 'en|nb')
    ->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Authenticated / signed routes
|--------------------------------------------------------------------------
*/

Route::get('/download/analysis/{file}', [AnalysisDownloadController::class, 'download'])
    ->middleware(['auth', 'signed'])
    ->name('download.analysis');

Route::get('/invitations/{token}', InvitationAcceptController::class)
    ->name('invitations.accept');
