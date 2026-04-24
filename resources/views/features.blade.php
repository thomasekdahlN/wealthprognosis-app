@extends('layouts.public')

@section('title', 'Features — Wealth Prognosis')
@section('description',
    'A complete feature overview: year-by-year prognosis, taxation, FIRE metrics, AI
    assistant, multi-tenancy, mortgage modelling and more.')

    @push('head')
        <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Features', 'item' => route('features')],
                ],
            ],
            [
                '@type' => 'ItemList',
                '@id' => route('features') . '#features',
                'name' => 'Wealth Prognosis features',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Prognosis engine', 'description' => 'Year-by-year simulation of income, expenses, mortgages, cash flow, taxes and asset value across pessimistic, realistic and optimistic scenarios.'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Taxation', 'description' => 'Complete coverage of fortune tax, property tax, income tax, capital tax, rental tax, company tax, dividend tax and the tax shield. Swedish and Swiss tax calculations are available in beta.'],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => 'Mortgages and loans', 'description' => 'Annuity mortgages, extra downpayments, tax-deductible interest and max-mortgage calculation.'],
                    ['@type' => 'ListItem', 'position' => 4, 'name' => 'F.I.R.E metrics', 'description' => 'Sell-down strategy for liquid assets while keeping essential assets such as house, cabin, car and boat.'],
                    ['@type' => 'ListItem', 'position' => 5, 'name' => 'AI assistant', 'description' => 'Natural-language English and Norwegian access to the full configuration via Google Gemini with tool-calling.'],
                    ['@type' => 'ListItem', 'position' => 6, 'name' => 'Multi-tenancy and security', 'description' => 'Team-scoped data, audit stamping, signed download URLs and a modern admin UI.'],
                    ['@type' => 'ListItem', 'position' => 7, 'name' => 'Assets and configuration', 'description' => '15+ asset types covering real estate, vehicles, pensions, financial assets, companies and liabilities.'],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endpush

    @php
        $groups = [
            [
                'title' => 'Prognosis engine',
                'intro' => 'The calculation core simulates every year between today and your expected death year.',
                'items' => [
                    [
                        'name' => 'Year-by-year simulation',
                        'body' =>
                            'Income, expenses, mortgages, cash flow, taxes and asset value computed per year for every asset you own.',
                    ],
                    [
                        'name' => 'Three scenarios',
                        'body' =>
                            'Run the same configuration as pessimistic, realistic and optimistic — change rates configurable per asset.',
                    ],
                    [
                        'name' => 'Transfers between assets',
                        'body' =>
                            'Move cash flow or asset value from one asset to another with correct taxation on realization.',
                    ],
                    [
                        'name' => 'Rule-based additions',
                        'body' =>
                            'Add fixed amounts, percentages of other assets, or derived values (e.g. 5% of salary to OTP).',
                    ],
                    [
                        'name' => 'Repeat &amp; milestone years',
                        'body' =>
                            'Use $pensionWishYear, $deathYear, $pensionOfficialYear as symbolic years that adapt to your life plan.',
                    ],
                    [
                        'name' => 'Excel export',
                        'body' =>
                            'Export the full prognosis to Excel with per-asset sheets, type sheets and a totals sheet.',
                    ],
                ],
            ],
            [
                'title' => 'Taxation',
                'intro' =>
                    'Complete coverage of Norwegian tax rules, year by year. Swedish and Swiss tax calculations are available in beta.',
                'items' => [
                    [
                        'name' => 'Fortune tax (formueskatt)',
                        'body' =>
                            'Calculated per year based on aggregated net wealth, with tiered rates from the tax configuration.',
                    ],
                    [
                        'name' => 'Property tax (eiendomsskatt)',
                        'body' => 'Per-municipality rates, including the tax-free threshold and bunnfradrag.',
                    ],
                    [
                        'name' => 'Income &amp; capital tax',
                        'body' =>
                            'Salary, capital gains, interest and dividend taxation with correct brackets and deductions.',
                    ],
                    [
                        'name' => 'Rental &amp; company tax',
                        'body' => 'Rental income, company income tax and dividend tax on distributions to private.',
                    ],
                    [
                        'name' => 'Tax shield (skjermingsfradrag)',
                        'body' => 'Correct shielding of dividends and capital gains against the base rate.',
                    ],
                    [
                        'name' => 'Realization &amp; transfer tax',
                        'body' =>
                            'Correct taxation when realizing assets inside a company before transferring to private.',
                    ],
                    [
                        'name' => 'Sweden &amp; Switzerland (beta)',
                        'body' =>
                            'Core Swedish taxation (kapitalinkomst, ISK/KF schablonskatt, kapitalvinst, statlig &amp; kommunal inkomstskatt) and Swiss taxation (federal + cantonal income, wealth tax, Säule 3a) are usable today in beta.',
                    ],
                ],
            ],
            [
                'title' => 'Mortgages &amp; loans',
                'intro' => 'Full mortgage modelling with tax-deductible interest.',
                'items' => [
                    [
                        'name' => 'Annuity mortgages',
                        'body' =>
                            'Term amount, interest, principal, gebyr and balance computed per year for the life of the loan.',
                    ],
                    [
                        'name' => 'Extra downpayments',
                        'body' =>
                            'Transfer cash flow to the mortgage to reduce principal — the engine recalculates years remaining.',
                    ],
                    [
                        'name' => 'Tax-deductible interest',
                        'body' => 'Deductible amount, rate and percent tracked per year and fed back into income tax.',
                    ],
                    [
                        'name' => 'Max mortgage calculation',
                        'body' => 'See how much you can borrow based on income, existing debt and property value.',
                    ],
                ],
            ],
            [
                'title' => 'F.I.R.E metrics',
                'intro' => 'Not the 4% rule — a sell-down strategy that keeps your essential assets.',
                'items' => [
                    [
                        'name' => 'Financial independence year',
                        'body' => 'See exactly when your liquid assets can cover your remaining lifetime expenses.',
                    ],
                    [
                        'name' => 'Sell-down simulation',
                        'body' =>
                            'Liquidate liquid assets to zero over your retirement span — with tax on each realization.',
                    ],
                    [
                        'name' => 'Essential assets kept',
                        'body' =>
                            'Your house, cabin, car and boat stay with you; the simulation only sells what you mark as liquid.',
                    ],
                ],
            ],
            [
                'title' => 'AI assistant',
                'intro' => 'Natural-language access to your entire financial configuration.',
                'items' => [
                    [
                        'name' => 'English &amp; Norwegian',
                        'body' =>
                            '"Legg til en tesla til verdi 200K med lån 100K over 7 år" or "Set my house value to 3.5M NOK" — both just work.',
                    ],
                    [
                        'name' => 'Powered by Gemini',
                        'body' =>
                            'Google Gemini under the hood; conversation history is stored per user so the assistant has context between questions.',
                    ],
                    [
                        'name' => 'Tool-calling',
                        'body' =>
                            'The AI can create assets, update mortgages, adjust values and explain tax computations through safe, scoped tools.',
                    ],
                    [
                        'name' => 'What-if analysis',
                        'body' =>
                            'Ask "what if I pay down the mortgage by 10K/month?" and get an explained comparison.',
                    ],
                ],
            ],
            [
                'title' => 'Multi-tenancy &amp; security',
                'intro' => 'Your data belongs to your team and nobody else.',
                'items' => [
                    [
                        'name' => 'Team-scoped data',
                        'body' =>
                            'A global scope filters every query by team_id — your assets never appear in another user\'s dashboard.',
                    ],
                    [
                        'name' => 'Audit stamping',
                        'body' =>
                            'created_by, updated_by, created_checksum and updated_checksum on every model, maintained automatically.',
                    ],
                    [
                        'name' => 'Signed download URLs',
                        'body' => 'Analysis files are served through signed, auth-protected routes.',
                    ],
                    [
                        'name' => 'Modern admin',
                        'body' => 'Fast admin UI with resource tables, forms, filters and bulk actions.',
                    ],
                ],
            ],
            [
                'title' => 'Assets &amp; configuration',
                'intro' => '15+ asset types covering everything you can own.',
                'items' => [
                    [
                        'name' => 'Real estate',
                        'body' => 'Houses, cabins, rental properties with property tax, maintenance and rental income.',
                    ],
                    [
                        'name' => 'Vehicles',
                        'body' => 'Cars, boats, motorcycles with depreciation, insurance and running costs.',
                    ],
                    [
                        'name' => 'Pensions',
                        'body' => 'OTP, IPS, folketrygd and private pensions with correct tax treatment.',
                    ],
                    [
                        'name' => 'Financial assets',
                        'body' => 'Stocks, bonds, mutual funds, ETFs, crypto, cash — each with its own tax profile.',
                    ],
                    [
                        'name' => 'Companies',
                        'body' => 'AS/ASA company ownership with dividend, income and transfer taxation.',
                    ],
                    [
                        'name' => 'Loans &amp; liabilities',
                        'body' => 'Mortgages, student loans, credit lines — all modelled as negative assets.',
                    ],
                ],
            ],
        ];
    @endphp

@section('content')
    <nav aria-label="Breadcrumb" class="max-w-7xl mx-auto px-6 lg:px-8 pt-6 text-sm text-slate-400">
        <ol class="flex items-center gap-2">
            <li><a href="{{ url('/') }}" class="hover:text-white transition">Home</a></li>
            <li aria-hidden="true" class="text-slate-600">/</li>
            <li aria-current="page" class="text-slate-200">Features</li>
        </ol>
    </nav>

    <section class="relative hero-gradient overflow-hidden" aria-labelledby="features-title">
        <div class="absolute inset-0 grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-5xl mx-auto px-6 lg:px-8 pt-12 pb-16 sm:pt-20 sm:pb-20 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                Features
            </span>
            <h1 id="features-title"
                class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                Everything Wealth Prognosis does,<br>in one place.
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                A complete planning platform covering prognosis, taxation, FIRE, AI, mortgages and more — built for
                long-term financial clarity.
            </p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-label="Feature groups">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 sm:py-24 space-y-20">
            @foreach ($groups as $index => $group)
                @php($groupId = 'feature-group-' . $index)
                <article class="grid grid-cols-1 lg:grid-cols-3 gap-10" aria-labelledby="{{ $groupId }}">
                    <div class="lg:col-span-1">
                        <h2 id="{{ $groupId }}" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                            {!! $group['title'] !!}</h2>
                        <p class="mt-4 text-slate-300 leading-relaxed">{{ $group['intro'] }}</p>
                    </div>
                    <dl class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        @foreach ($group['items'] as $item)
                            <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-5">
                                <dt class="flex items-center gap-2 font-semibold text-white">
                                    <svg class="w-4 h-4 text-brand-300 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="currentColor" aria-hidden="true" focusable="false">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    <span>{!! $item['name'] !!}</span>
                                </dt>
                                <dd class="mt-2 text-sm text-slate-300 leading-relaxed">{!! $item['body'] !!}</dd>
                            </div>
                        @endforeach
                    </dl>
                </article>
            @endforeach
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="features-cta-title">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 id="features-cta-title" class="text-3xl sm:text-5xl font-bold tracking-tight text-white">Ready to see
                your future?</h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">Open the admin dashboard, configure your first
                asset, and run the simulation.</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/admin') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    Open dashboard
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                        aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                <a href="{{ url('/') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    Back to home
                </a>
            </div>
        </div>
    </section>
@endsection
