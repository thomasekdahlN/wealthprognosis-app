@extends('layouts.public')

@section('title', 'Wealth Prognosis — plan your financial future')
@section('description',
    'Track every asset, run year-by-year financial prognoses until your death date, and get
    AI-powered insights on taxes, FIRE and cash flow.')
@section('og_type', 'website')

@push('head')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'SoftwareApplication',
                '@id' => url('/') . '#software',
                'name' => 'Wealth Prognosis',
                'applicationCategory' => 'FinanceApplication',
                'operatingSystem' => 'Web',
                'description' =>
                    'Financial planning and simulation system that tracks every asset, applies accurate taxation, and forecasts your economy year by year until your death date with AI-powered insights — across pessimistic, realistic and optimistic scenarios.',
                'url' => url('/'),
                'softwareVersion' => '1.0',
                'inLanguage' => ['en', 'no'],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '79',
                    'priceCurrency' => 'NOK',
                    'url' => url('/pricing'),
                ],
                'featureList' => [
                    'Year-by-year financial prognosis',
                    'Complete taxation (fortune, property, income, capital, pension, rental, company, dividend, tax shield)',
                    'FIRE metrics and retirement readiness',
                    'AI financial assistant powered by Google Gemini',
                    'Mortgage modelling with annuity loans',
                    'Multi-tenant team-scoped data',
                    '15+ asset types including stocks, bonds, crypto, property, companies',
                ],
                'publisher' => ['@id' => url('/') . '#organization'],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => url('/'),
                    ],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@php
    $heroFeatures = [
        [
            'title' => 'Year-by-year prognosis',
            'body' =>
                'Simulate income, expenses, mortgages, cash flow and taxes from today until your death year — across pessimistic, realistic and optimistic scenarios.',
            'icon' =>
                'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
        ],
        [
            'title' => 'Complete Norwegian tax',
            'body' =>
                'Fortune tax, property tax, income tax, capital tax, pension tax, rental tax, company tax, dividend tax and the tax shield — all computed per year, per asset. Swedish and Swiss tax calculations are available in beta.',
            'icon' =>
                'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
        ],
        [
            'title' => 'FIRE metrics',
            'body' =>
                'Know exactly when you can retire early. Liquidate liquid assets until zero while keeping your house, cabin, boat and car.',
            'icon' =>
                'M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z',
        ],
        [
            'title' => 'AI financial assistant',
            'body' =>
                'Ask questions in natural language — English or Norwegian. Add assets, update mortgages and get plain-language explanations powered by Google Gemini.',
            'icon' =>
                'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z',
        ],
        [
            'title' => 'Mortgage modelling',
            'body' =>
                'Annuity loans, extra downpayments, tax-deductible interest and dynamic recalculation when your strategy changes.',
            'icon' =>
                'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75',
        ],
        [
            'title' => 'Multi-tenant &amp; secure',
            'body' =>
                'Team-scoped data, audit stamping and signed download URLs. Your data never mixes with another user\'s.',
            'icon' =>
                'M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z',
        ],
    ];

    $scenarios = [
        [
            'badge' => '−',
            'name' => 'Pessimistic',
            'body' =>
                'Lower returns, higher inflation, weaker wage growth. Stress-test whether your plan survives a bad decade before you commit to it.',
        ],
        [
            'badge' => '=',
            'name' => 'Realistic',
            'body' =>
                'Historical averages per asset class, current Norwegian tax rules and your configured change rates. Your working baseline plan.',
        ],
        [
            'badge' => '+',
            'name' => 'Optimistic',
            'body' =>
                'Favourable markets, strong wage growth, best-case taxation outcomes. The upside you are aiming at — modelled year by year.',
        ],
    ];

    $simulationCoverage = [
        'Yearly cash flow for every asset — income, expenses, mortgage, taxes',
        'Fortune, property, income, capital, pension, rental, company, dividend and shielding tax',
        'Wealth trajectory and FIRE year across every scenario',
        'Mortgage amortisation, extra downpayments and deductible interest',
        'Pension payouts: offentlig, tjenestepensjon and private savings',
        'Per-asset change rates — returns, inflation, maintenance, wage growth',
    ];

    $simulationEvents = [
        'Sell an asset in any future year',
        'Transfer an asset from a company to private (with realisation tax)',
        'Change mortgage terms, interest rate or refinance mid-plan',
        'Early, normal or delayed retirement side by side',
        'Draw down liquid assets to zero while keeping house, cabin, car and boat',
        'Add a new salary, pension or inheritance at any future date',
    ];
@endphp

@section('content')
    <section id="overview" class="relative hero-gradient overflow-hidden" aria-labelledby="hero-title">
        <div class="absolute inset-0 grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 pt-20 pb-24 sm:pt-28 sm:pb-32">
            <div class="max-w-3xl">
                <h1 id="hero-title" class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                    See your <span
                        class="bg-gradient-to-r from-brand-300 via-brand-400 to-emerald-200 bg-clip-text text-transparent">financial
                        future</span>, year by year.
                </h1>
                <p class="mt-6 text-lg sm:text-xl text-slate-300 leading-relaxed">
                    Wealth Prognosis is a comprehensive planning system that tracks every asset you own, applies accurate
                    taxation, and simulates your economy from today until your death date — with AI insights that explain
                    what the numbers actually mean for you.
                </p>
                <div class="mt-10 flex flex-col sm:flex-row gap-4">
                    <a href="{{ url('/admin') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                        Start planning
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            aria-hidden="true" focusable="false">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <a href="{{ route('features') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                        Explore features
                    </a>
                </div>
            </div>

            <dl class="mt-20 grid grid-cols-2 lg:grid-cols-4 gap-6 max-w-4xl" aria-label="Key project facts">
                @foreach ([['label' => 'Asset types', 'value' => '40+'], ['label' => 'Tax types', 'value' => '29'], ['label' => 'Property tax types', 'value' => '327'], ['label' => 'Tax rules', 'value' => 'NO · SE · CH']] as $stat)
                    <div class="rounded-2xl border border-white/5 bg-white/[0.02] backdrop-blur p-5">
                        <dt class="text-xs uppercase tracking-wider text-slate-300">{{ $stat['label'] }}</dt>
                        <dd class="mt-1.5 text-xl font-bold text-white">{{ $stat['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 sm:py-28">
            <div class="max-w-2xl">
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">Everything you need to plan a life.
                </h2>
                <p class="mt-4 text-lg text-slate-300">From your first salary to the year you retire — model every asset,
                    every tax, every transfer.</p>
            </div>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($heroFeatures as $feature)
                    <div
                        class="group rounded-2xl border border-white/5 bg-gradient-to-b from-white/[0.04] to-transparent p-6 hover:border-brand-400/30 hover:bg-white/[0.06] transition">
                        <div
                            class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-brand-500/10 text-brand-300 ring-1 ring-brand-400/20 group-hover:scale-110 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75"
                                stroke="currentColor" aria-hidden="true" focusable="false">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-white">{!! $feature['title'] !!}</h3>
                        <p class="mt-2 text-sm text-slate-300 leading-relaxed">{!! $feature['body'] !!}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 text-center">
                <a href="{{ route('features') }}"
                    class="inline-flex items-center gap-2 text-brand-300 hover:text-brand-200 font-semibold transition">
                    See all features
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                        aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="relative border-t border-white/5 bg-gradient-to-b from-slate-950 to-slate-900/50">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 sm:py-28">
            <div class="max-w-2xl">
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">How it works.</h2>
                <p class="mt-4 text-lg text-slate-300">Four steps from "I have some savings" to "here's exactly when I can
                    retire".</p>
            </div>

            <ol class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ([['step' => '01', 'title' => 'Configure your life', 'body' => 'Enter birth year, wished retirement year, official pension year and expected death year. This defines your simulation horizon.'], ['step' => '02', 'title' => 'Add your assets', 'body' => 'Houses, cabins, cars, pensions, stocks, bonds, crypto, companies — with income, expenses, mortgages and change rates per year.'], ['step' => '03', 'title' => 'Run the prognosis', 'body' => 'The engine computes taxes, cash flow, asset value and FIRE metrics year by year across pessimistic, realistic and optimistic scenarios.'], ['step' => '04', 'title' => 'Ask the AI', 'body' => 'Get plain-language explanations, what-if analysis and recommendations — or export the full simulation to Excel.']] as $step)
                    <li class="relative rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                        <div class="text-xs font-mono text-brand-300 tracking-widest" aria-hidden="true">
                            {{ $step['step'] }}</div>
                        <h3 class="mt-3 text-lg font-semibold text-white"><span class="sr-only">Step
                                {{ (int) $step['step'] }}:</span> {{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm text-slate-300 leading-relaxed">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section id="simulation" class="relative border-t border-white/5 bg-slate-950" aria-labelledby="simulation-title">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 sm:py-28">
            <div class="max-w-2xl">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                    Simulation engine
                </span>
                <h2 id="simulation-title" class="mt-5 text-3xl sm:text-4xl font-bold tracking-tight text-white">Three
                    scenarios. One life.</h2>
                <p class="mt-4 text-lg text-slate-300 leading-relaxed">Every prognosis runs three times — pessimistic,
                    realistic and optimistic — so you can see how resilient your plan is before you bet on it. Every
                    asset, every tax, every year.</p>
            </div>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($scenarios as $scenario)
                    <article class="rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                        <span
                            class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-brand-500/10 text-brand-300 ring-1 ring-brand-400/20 text-xl font-bold"
                            aria-hidden="true">{{ $scenario['badge'] }}</span>
                        <h3 class="mt-4 text-lg font-semibold text-white">{{ $scenario['name'] }}</h3>
                        <p class="mt-2 text-sm text-slate-300 leading-relaxed">{{ $scenario['body'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="mt-16 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-8">
                    <h3 class="text-xl font-semibold text-white">What the simulation covers</h3>
                    <ul class="mt-6 space-y-3 text-sm text-slate-300">
                        @foreach ($simulationCoverage as $item)
                            <li class="flex items-start gap-3">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-brand-300" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2.5" stroke="currentColor" aria-hidden="true" focusable="false">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-8">
                    <h3 class="text-xl font-semibold text-white">What you can simulate</h3>
                    <ul class="mt-6 space-y-3 text-sm text-slate-300">
                        @foreach ($simulationEvents as $item)
                            <li class="flex items-start gap-3">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-brand-300" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"
                                    focusable="false">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-white">Plan the next 50 years in an afternoon.
            </h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">Open the dashboard, add your first asset and watch
                your
                financial future take shape.</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/admin') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    Open dashboard
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                        aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                <a href="{{ route('features') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    Browse features
                </a>
            </div>
        </div>
    </section>
@endsection
