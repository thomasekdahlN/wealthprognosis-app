<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

@php
    $pageTitle = trim($__env->yieldContent('title', 'Wealth Prognosis'));
    $pageDescription = trim(
        $__env->yieldContent(
            'description',
            'Wealth Prognosis — track your assets, simulate year-by-year financial forecasts, and plan for financial independence with AI-powered insights.',
        ),
    );
    $canonicalUrl = url()->current();
    $ogImage = asset('logo.png');
    $siteName = 'Wealth Prognosis';
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1">
    <meta name="theme-color" content="#020617">
    <meta name="color-scheme" content="dark light">

    <title>{{ $pageTitle }} — Financial planning &amp; simulation</title>

    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $__env->yieldContent('og_type', 'website') }}">
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
                'url' => url('/'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $ogImage,
                ],
                'sameAs' => [
                    'https://github.com/thomasek/wealthprognosis-app',
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/') . '#website',
                'url' => url('/'),
                'name' => $siteName,
                'description' => 'Open-source financial planning and simulation system with year-by-year prognosis, Norwegian taxation and an AI assistant.',
                'publisher' => ['@id' => url('/') . '#organization'],
                'inLanguage' => app()->getLocale(),
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @stack('head')
</head>

<body class="antialiased bg-slate-950 text-slate-200 selection:bg-brand-500/30 selection:text-white">

    <a href="#main-content" class="skip-link">Skip to main content</a>

    <header class="fixed top-0 inset-x-0 z-50 backdrop-blur-lg bg-slate-950/70 border-b border-white/5" role="banner">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <nav class="flex items-center justify-between h-16" aria-label="Primary">
                <a href="{{ url('/') }}" class="flex items-center gap-3 group"
                    aria-label="{{ $siteName }} — home">
                    <img src="{{ asset('logo.png') }}" alt=""
                        class="h-8 w-8 rounded-lg ring-1 ring-white/10 group-hover:ring-brand-400/40 transition">
                    <span class="font-semibold tracking-tight text-white">Wealth Prognosis</span>
                </a>

                <div class="hidden md:flex items-center gap-8 text-sm text-slate-300">
                    <a href="{{ url('/') }}#overview"
                        class="hover:text-white transition"@if (request()->path() === '/') aria-current="page" @endif>Overview</a>
                    <a href="{{ route('features') }}"
                        class="hover:text-white transition"@if (request()->routeIs('features')) aria-current="page" @endif>Features</a>
                    <a href="{{ route('pricing') }}"
                        class="hover:text-white transition"@if (request()->routeIs('pricing')) aria-current="page" @endif>Pricing</a>
                    <a href="{{ url('/') }}#how-it-works" class="hover:text-white transition">How it works</a>
                    <a href="{{ route('about') }}"
                        class="hover:text-white transition"@if (request()->routeIs('about')) aria-current="page" @endif>About</a>
                    <a href="{{ route('faq') }}"
                        class="hover:text-white transition"@if (request()->routeIs('faq')) aria-current="page" @endif>FAQ</a>
                    <a href="https://github.com/thomasek/wealthprognosis-app" target="_blank" rel="noopener noreferrer"
                        class="hover:text-white transition">GitHub<span class="sr-only"> (opens in a new tab)</span></a>
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ url('/admin') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-4 py-2 text-sm transition">
                            Open dashboard
                        </a>
                    @else
                        <a href="{{ url('/admin/login') }}"
                            class="hidden sm:inline-flex text-sm text-slate-300 hover:text-white transition">Sign in</a>
                        <a href="{{ url('/admin') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-4 py-2 text-sm transition shadow-lg shadow-brand-500/20">
                            Get started
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

    <footer class="border-t border-white/5 bg-slate-950" role="contentinfo" aria-label="Site footer">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('logo.png') }}" alt=""
                            class="h-8 w-8 rounded-lg ring-1 ring-white/10">
                        <span class="font-semibold text-white">Wealth Prognosis</span>
                    </div>
                    <p class="mt-4 text-sm text-slate-300 max-w-md">
                        Open-source financial planning and simulation system. Track assets, run year-by-year prognoses,
                        and plan your path to financial independence.
                    </p>
                </div>
                <nav aria-label="Product">
                    <h2 class="text-sm font-semibold text-white">Product</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-300">
                        <li><a href="{{ route('features') }}" class="hover:text-white transition">Features</a></li>
                        <li><a href="{{ route('pricing') }}" class="hover:text-white transition">Pricing</a></li>
                        <li><a href="{{ url('/') }}#how-it-works" class="hover:text-white transition">How it
                                works</a></li>
                        <li><a href="{{ url('/admin') }}" class="hover:text-white transition">Dashboard</a></li>
                    </ul>
                </nav>
                <nav aria-label="Company">
                    <h2 class="text-sm font-semibold text-white">Company</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-300">
                        <li><a href="{{ route('about') }}" class="hover:text-white transition">About</a></li>
                        <li><a href="{{ route('faq') }}" class="hover:text-white transition">FAQ</a></li>
                        <li><a href="https://github.com/thomasek/wealthprognosis-app" target="_blank"
                                rel="noopener noreferrer" class="hover:text-white transition">GitHub<span
                                    class="sr-only"> (opens in a new tab)</span></a></li>
                        <li><a href="https://github.com/thomasek/wealthprognosis-app#readme" target="_blank"
                                rel="noopener noreferrer" class="hover:text-white transition">Documentation<span
                                    class="sr-only"> (opens in a new tab)</span></a></li>
                    </ul>
                </nav>
            </div>
            <div
                class="mt-10 pt-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-400">&copy; {{ date('Y') }} Wealth Prognosis. Built with Laravel
                    &amp;
                    Filament.</p>
                <p class="text-xs text-slate-400">Made for long-term thinkers.</p>
            </div>
        </div>
    </footer>

</body>

</html>
