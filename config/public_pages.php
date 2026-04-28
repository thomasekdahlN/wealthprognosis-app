<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Markdown content path
    |--------------------------------------------------------------------------
    |
    | Absolute filesystem path that holds the per-locale Markdown source files
    | for the public marketing site. The renderer resolves files as
    | "{path}/{locale}/{slug}.md" and falls back to the default locale when a
    | translation is missing.
    |
    */
    'content_path' => resource_path('content'),

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | The first entry is treated as the default and the fallback locale.
    |
    */
    'locales' => ['en', 'nb'],

    'default_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Locale display labels
    |--------------------------------------------------------------------------
    |
    | Used by the language switcher. Keyed by locale code.
    |
    | @var array<string, string>
    */
    'locale_labels' => [
        'en' => 'English',
        'nb' => 'Norsk',
    ],
];
