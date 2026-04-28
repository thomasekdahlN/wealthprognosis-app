<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MarkdownPageRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Renders the public marketing pages from per-locale Markdown source files.
 *
 * The route resolves a `{locale}` prefix and a `{slug}` segment (defaulting
 * to `home`). The selected file's front matter chooses the Blade template
 * that consumes its structured data.
 */
final class PublicPageController extends Controller
{
    public function __construct(
        private readonly MarkdownPageRenderer $renderer,
    ) {}

    /**
     * Render a Markdown-driven public page for a given locale + slug.
     */
    public function show(string $locale, string $slug = 'home'): View
    {
        $page = $this->renderer->find($slug, $locale);

        if ($page === null) {
            throw new NotFoundHttpException("Public page [{$locale}/{$slug}] not found.");
        }

        return view('public.'.$page->template, [
            'page' => $page,
        ]);
    }

    /**
     * Switch the active locale and redirect to the same slug under the new
     * locale prefix. Used by the language switcher in the public layout.
     */
    public function switchLocale(Request $request, string $locale): RedirectResponse
    {
        /** @var array<int, string> $supported */
        $supported = (array) config('public_pages.locales', ['en']);

        if (! in_array($locale, $supported, true)) {
            throw new NotFoundHttpException("Locale [{$locale}] is not supported.");
        }

        $request->session()->put('locale', $locale);

        $slug = (string) $request->query('to', 'home');

        return redirect()->to('/'.$locale.($slug === 'home' ? '' : '/'.$slug));
    }
}
