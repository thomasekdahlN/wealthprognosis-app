<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

@php
    /** @var \App\Support\ValueObjects\MarkdownPage|null $page */
    $page = $page ?? null;

    $currentLocale = app()->getLocale();
    $supportedLocales = (array) config('public_pages.locales', ['en', 'nb']);

    $currentSlug = $page?->slug ?? 'home';

    $localeUrl = static function (string $locale) use ($currentSlug): string {
        return url('/' . $locale . ($currentSlug === 'home' ? '' : '/' . $currentSlug));
    };

    $pageTitle =
        $page?->title !== null && $page->title !== ''
            ? $page->title
            : trim($__env->yieldContent('title', __('public.site_name')));
    $pageDescription =
        $page?->description !== null && $page->description !== ''
            ? $page->description
            : trim($__env->yieldContent('description', ''));
    $canonicalUrl = url()->current();
    $ogImage = asset('logo.png');
    $siteName = __('public.site_name');
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1">
    <meta name="theme-color" content="#020617">
    <meta name="color-scheme" content="dark light">

    <title>{{ $pageTitle }} — {{ __('public.tagline') }}</title>

    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

    @foreach ($supportedLocales as $altLocale)
        <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $localeUrl($altLocale) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default"
        href="{{ $localeUrl((string) config('public_pages.default_locale', 'en')) }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $page?->ogType ?: $__env->yieldContent('og_type', 'website') }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                    },
                },
            },
        };
    </script>

    <style>
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        }

        .grid-pattern {
            background-image:
                radial-gradient(circle at 1px 1px, rgba(16, 185, 129, 0.15) 1px, transparent 0);
            background-size: 24px 24px;
        }

        .hero-gradient {
            background:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(16, 185, 129, 0.25), transparent),
                radial-gradient(ellipse 60% 50% at 80% 120%, rgba(59, 130, 246, 0.15), transparent);
        }

        /* WCAG: visible focus for keyboard users on a dark background */
        :focus-visible {
            outline: 2px solid #34d399;
            outline-offset: 2px;
            border-radius: 6px;
        }

        /* WCAG: reduced motion */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
                scroll-behavior: auto !important;
            }
        }

        /* Skip link — hidden until focused */
        .skip-link {
            position: absolute;
            left: 1rem;
            top: -3rem;
            z-index: 100;
            padding: 0.5rem 1rem;
            background: #10b981;
            color: #020617;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: top 0.15s ease;
        }

        .skip-link:focus {
            top: 1rem;
        }
    </style>

    {{-- Schema.org: Organization + WebSite (site-wide) --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/') . '#organization',
                'name' => $siteName,
                'legalName' => 'Ekdahl Enterprises AS',
                'url' => url('/'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $ogImage,
                ],
                'email' => 'thomas@ekdahl.no',
                'telephone' => '+4791143630',
                'taxID' => 'NO933662541',
                'vatID' => 'NO933662541MVA',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Smørbukkveien 3',
                    'postalCode' => '3123',
                    'addressLocality' => 'Tønsberg',
                    'addressCountry' => 'NO',
                ],
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'contactType' => 'customer support',
                    'email' => 'thomas@ekdahl.no',
                    'telephone' => '+4791143630',
                    'areaServed' => 'NO',
                    'availableLanguage' => ['en', 'no'],
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/') . '#website',
                'url' => url('/'),
                'name' => $siteName,
                'description' => 'Financial planning and simulation system with year-by-year prognosis, taxation and an AI assistant.',
                'publisher' => ['@id' => url('/') . '#organization'],
                'inLanguage' => app()->getLocale(),
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @stack('head')
</head>

<body class="antialiased bg-slate-950 text-slate-200 selection:bg-brand-500/30 selection:text-white">

    <a href="#main-content" class="skip-link">{{ __('public.skip_to_main') }}</a>

    <header class="fixed top-0 inset-x-0 z-50 backdrop-blur-lg bg-slate-950/70 border-b border-white/5" role="banner">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <nav class="flex items-center justify-between h-16" aria-label="{{ __('public.nav.primary') }}">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group"
                    aria-label="{{ __('public.aria_home', ['site' => $siteName]) }}">
                    <img src="{{ asset('logo.png') }}" alt=""
                        class="h-8 w-8 rounded-lg ring-1 ring-white/10 group-hover:ring-brand-400/40 transition">
                    <span class="font-semibold tracking-tight text-white">{{ $siteName }}</span>
                </a>

                <div class="hidden md:flex items-center gap-8 text-sm text-slate-300">
                    <a href="{{ route('home') }}#overview"
                        class="hover:text-white transition"@if (request()->routeIs('home')) aria-current="page" @endif>{{ __('public.nav.overview') }}</a>
                    <a href="{{ route('features') }}"
                        class="hover:text-white transition"@if (request()->routeIs('features')) aria-current="page" @endif>{{ __('public.nav.features') }}</a>
                    <a href="{{ route('use-cases') }}"
                        class="hover:text-white transition"@if (request()->routeIs('use-cases')) aria-current="page" @endif>{{ __('public.nav.use_cases') }}</a>
                    <a href="{{ route('pricing') }}"
                        class="hover:text-white transition"@if (request()->routeIs('pricing')) aria-current="page" @endif>{{ __('public.nav.pricing') }}</a>
                    <a href="{{ route('glossary') }}"
                        class="hover:text-white transition"@if (request()->routeIs('glossary')) aria-current="page" @endif>{{ __('public.nav.glossary') }}</a>
                    <a href="{{ route('methodology') }}"
                        class="hover:text-white transition"@if (request()->routeIs('methodology')) aria-current="page" @endif>{{ __('public.nav.methodology') }}</a>
                    <a href="{{ route('about') }}"
                        class="hover:text-white transition"@if (request()->routeIs('about')) aria-current="page" @endif>{{ __('public.nav.about') }}</a>
                    <a href="{{ route('faq') }}"
                        class="hover:text-white transition"@if (request()->routeIs('faq')) aria-current="page" @endif>{{ __('public.nav.faq') }}</a>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden sm:inline-flex items-center rounded-lg border border-white/10 bg-white/[0.02] p-0.5"
                        role="group" aria-label="{{ __('public.nav.language') }}">
                        @foreach ($supportedLocales as $altLocale)
                            <a href="{{ $localeUrl($altLocale) }}" hreflang="{{ $altLocale }}"
                                class="px-2.5 py-1 text-xs font-semibold uppercase tracking-wider rounded-md transition {{ $altLocale === $currentLocale ? 'bg-brand-500/20 text-brand-200' : 'text-slate-400 hover:text-white' }}"
                                @if ($altLocale === $currentLocale) aria-current="true" @endif>{{ $altLocale }}</a>
                        @endforeach
                    </div>

                    @auth
                        <a href="{{ url('/admin') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-4 py-2 text-sm transition">
                            {{ __('public.nav.open_dashboard') }}
                        </a>
                    @else
                        <a href="{{ url('/admin/login') }}"
                            class="hidden sm:inline-flex text-sm text-slate-300 hover:text-white transition">{{ __('public.nav.sign_in') }}</a>
                        <a href="{{ url('/admin') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-4 py-2 text-sm transition shadow-lg shadow-brand-500/20">
                            {{ __('public.nav.get_started') }}
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                aria-hidden="true" focusable="false">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main id="main-content" class="pt-16" tabindex="-1">
        @yield('content')
    </main>

    <footer class="border-t border-white/5 bg-slate-950" role="contentinfo"
        aria-label="{{ __('public.footer.banner') }}">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
            <div class="grid grid-cols-2 md:grid-cols-6 gap-8">
                <div class="col-span-2 md:col-span-2">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('logo.png') }}" alt=""
                            class="h-8 w-8 rounded-lg ring-1 ring-white/10">
                        <span class="font-semibold text-white">{{ $siteName }}</span>
                    </div>
                    <p class="mt-4 text-sm text-slate-300 max-w-md">
                        {{ __('public.footer.description') }}
                    </p>

                    <address class="mt-6 not-italic text-sm text-slate-300 leading-relaxed" itemscope
                        itemtype="https://schema.org/Organization">
                        <span class="block font-semibold text-white" itemprop="legalName">Ekdahl Enterprises AS</span>
                        <span class="block text-slate-400">Org.nr:
                            <span itemprop="taxID">933 662 541</span>
                        </span>
                        <span class="block mt-2" itemprop="address" itemscope
                            itemtype="https://schema.org/PostalAddress">
                            <span class="block" itemprop="streetAddress">Smørbukkveien 3</span>
                            <span class="block">
                                <span itemprop="postalCode">3123</span>
                                <span itemprop="addressLocality">Tønsberg</span>,
                                <span itemprop="addressCountry">Norge</span>
                            </span>
                        </span>
                    </address>
                </div>
                <nav aria-label="{{ __('public.footer.product') }}">
                    <h2 class="text-sm font-semibold text-white">{{ __('public.footer.product') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-300">
                        <li><a href="{{ route('features') }}"
                                class="hover:text-white transition">{{ __('public.nav.features') }}</a></li>
                        <li><a href="{{ route('pricing') }}"
                                class="hover:text-white transition">{{ __('public.nav.pricing') }}</a></li>
                        <li><a href="{{ route('use-cases') }}"
                                class="hover:text-white transition">{{ __('public.nav.use_cases') }}</a></li>
                        <li><a href="{{ url('/admin') }}"
                                class="hover:text-white transition">{{ __('public.footer.dashboard') }}</a></li>
                    </ul>
                </nav>
                <nav aria-label="{{ __('public.footer.resources') }}">
                    <h2 class="text-sm font-semibold text-white">{{ __('public.footer.resources') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-300">
                        <li><a href="{{ route('glossary') }}"
                                class="hover:text-white transition">{{ __('public.nav.glossary') }}</a></li>
                        <li><a href="{{ route('methodology') }}"
                                class="hover:text-white transition">{{ __('public.nav.methodology') }}</a></li>
                        <li><a href="{{ route('faq') }}"
                                class="hover:text-white transition">{{ __('public.nav.faq') }}</a></li>
                        <li><a href="{{ route('home') }}#how-it-works"
                                class="hover:text-white transition">{{ __('public.footer.how_it_works') }}</a></li>
                    </ul>
                </nav>
                <nav aria-label="{{ __('public.footer.company') }}">
                    <h2 class="text-sm font-semibold text-white">{{ __('public.footer.company') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-300">
                        <li><a href="{{ route('about') }}"
                                class="hover:text-white transition">{{ __('public.nav.about') }}</a></li>
                        <li><a href="{{ route('legal') }}"
                                class="hover:text-white transition">{{ __('public.footer.legal') }}</a></li>
                    </ul>
                </nav>
                <nav aria-label="{{ __('public.footer.contact') }}">
                    <h2 class="text-sm font-semibold text-white">{{ __('public.footer.contact') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-300">
                        <li>
                            <a href="mailto:thomas@ekdahl.no"
                                class="inline-flex items-center gap-2 hover:text-white transition break-all">
                                <svg class="w-4 h-4 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" aria-hidden="true" focusable="false">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                                thomas@ekdahl.no
                            </a>
                        </li>
                        <li>
                            <a href="tel:+4791143630"
                                class="inline-flex items-center gap-2 hover:text-white transition">
                                <svg class="w-4 h-4 shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" aria-hidden="true" focusable="false">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                </svg>
                                +47 911 43 630
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <div
                class="mt-10 pt-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-400">{!! __('public.footer.rights', ['year' => date('Y'), 'company' => 'Ekdahl Enterprises AS']) !!}</p>
                <p class="text-xs text-slate-400">{{ __('public.footer.made_for') }}</p>
            </div>
        </div>
    </footer>

</body>

</html>
