@extends('layouts.public')

@section('title', 'About — Wealth Prognosis')
@section('description',
    'Why Wealth Prognosis exists, who builds it, and the principles behind the system — open source,
    privacy-first, accurate Norwegian taxation and long-horizon planning.')

@push('head')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'About', 'item' => route('about')],
                ],
            ],
            [
                '@type' => 'AboutPage',
                '@id' => route('about') . '#about',
                'name' => 'About Wealth Prognosis',
                'description' => 'Open-source, privacy-first financial planning and simulation system with accurate Norwegian taxation.',
                'isPartOf' => ['@id' => url('/') . '#website'],
                'about' => ['@id' => url('/') . '#software'],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@php
    $principles = [
        [
            'title' => 'Accuracy over approximation',
            'body' =>
                'Norwegian taxation is complex. We model the real brackets, deductions, shielding and realization rules — not rough percentages. If the law changes, the tax configuration changes with it.',
        ],
        [
            'title' => 'Long horizons, not just next month',
            'body' =>
                'Most planners stop at a 10-year cash flow projection. Wealth Prognosis simulates every year from today until your death year so you can see how decisions compound across an entire life.',
        ],
        [
            'title' => 'Open source by default',
            'body' =>
                'The full source is available under the MIT licence. You can read it, run it, extend it or self-host it. A hosted plan exists for people who want convenience, not lock-in.',
        ],
        [
            'title' => 'Privacy-first',
            'body' =>
                'Your financial data is the most sensitive data you own. Team-scoped queries, audit stamping and signed download URLs are built into the core — not bolted on.',
        ],
    ];

    $stack = [
        ['label' => 'Framework', 'value' => 'Laravel 13'],
        ['label' => 'Admin UI', 'value' => 'Filament 5'],
        ['label' => 'AI', 'value' => 'Google Gemini'],
        ['label' => 'Database', 'value' => 'PostgreSQL'],
        ['label' => 'Language', 'value' => 'PHP 8.5'],
        ['label' => 'Licence', 'value' => 'MIT'],
    ];
@endphp

@section('content')
    <nav aria-label="Breadcrumb" class="max-w-7xl mx-auto px-6 lg:px-8 pt-6 text-sm text-slate-400">
        <ol class="flex items-center gap-2">
            <li><a href="{{ url('/') }}" class="hover:text-white transition">Home</a></li>
            <li aria-hidden="true" class="text-slate-600">/</li>
            <li aria-current="page" class="text-slate-200">About</li>
        </ol>
    </nav>

    <section class="relative hero-gradient overflow-hidden" aria-labelledby="about-title">
        <div class="absolute inset-0 grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-6 lg:px-8 pt-12 pb-16 sm:pt-20 sm:pb-20 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                About
            </span>
            <h1 id="about-title"
                class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                Built for clarity <span
                    class="bg-gradient-to-r from-brand-300 via-brand-400 to-emerald-200 bg-clip-text text-transparent">over
                    decades</span>.
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 leading-relaxed">
                Most financial tools optimise for this quarter. Wealth Prognosis optimises for the next fifty years — with
                accurate Norwegian taxation, transparent calculations and the option to self-host everything.
            </p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="mission-title">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 py-16 sm:py-20">
            <h2 id="mission-title" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">Why this exists</h2>
            <div class="mt-6 space-y-5 text-slate-300 leading-relaxed">
                <p>Personal finance software either stays so simple that it cannot answer real questions, or so complex
                    that only accountants can use it. Norwegian tax rules make this worse: fortune tax, property tax,
                    tax shielding, realization tax inside companies — these are not covered well by off-the-shelf tools.
                </p>
                <p>Wealth Prognosis started as a personal project to answer one question: <em class="text-white">when
                        can I actually retire, accounting for every tax, every asset, every year?</em> What started as a
                    spreadsheet became a Laravel application, then a Filament admin, then an AI-assisted planner.</p>
                <p>The result is a system that runs the same year-by-year simulation across pessimistic, realistic and
                    optimistic scenarios, explains what the numbers mean, and lets you keep your data on your own
                    infrastructure if you want to.</p>
            </div>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="principles-title">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 sm:py-24">
            <h2 id="principles-title" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">Principles</h2>
            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($principles as $principle)
                    <article class="rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                        <h3 class="text-lg font-semibold text-white">{{ $principle['title'] }}</h3>
                        <p class="mt-3 text-sm text-slate-300 leading-relaxed">{{ $principle['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="stack-title">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 sm:py-20">
            <h2 id="stack-title" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">Built on</h2>
            <dl class="mt-8 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4" aria-label="Technology stack">
                @foreach ($stack as $tech)
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                        <dt class="text-xs uppercase tracking-wider text-slate-300">{{ $tech['label'] }}</dt>
                        <dd class="mt-1.5 text-base font-semibold text-white">{{ $tech['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="about-cta-title">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 id="about-cta-title" class="text-3xl sm:text-5xl font-bold tracking-tight text-white">See it run on your
                own numbers.</h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">Open the dashboard, add a single asset, and watch
                the prognosis take shape.</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('pricing') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    See pricing
                </a>
                <a href="{{ route('features') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    Browse features
                </a>
            </div>
        </div>
    </section>
@endsection
