@extends('layouts.public')

@section('title', 'Use cases — who Wealth Prognosis is for')
@section('description',
    'Concrete use cases for Wealth Prognosis: planning F.I.R.E, managing property portfolios, running
    a one-person AS, comparing early vs normal retirement, modelling inheritance and company-to-private transfers.')
@section('og_type', 'article')

@php
    $cases = [
        [
            'slug' => 'fire',
            'badge' => 'F.I.R.E',
            'title' => 'Planning for financial independence',
            'audience' => 'Employees and self-employed people aiming to retire years before the official pension age.',
            'problem' =>
                'A 4% rule spreadsheet gives you a single number. It does not account for Norwegian fortune tax, capital-gains tax on fund sales, bracket-tax effects on pension income, or the fact that your house, cabin and car are not part of the sell-down.',
            'how' => [
                'Configure birth year, wished retirement year, official pension year and expected death year.',
                'Add every asset with realistic change rates — equity funds, ASK, pension, bank, property.',
                'Mark liquid vs. non-liquid. The engine liquidates liquid assets evenly from retirement to death.',
                'Run the three-scenario simulation. See whether the pessimistic scenario still clears expenses.',
                'Ask the AI: "hvor mye må jeg spare i aksjefondet per måned for å kunne gå av ved 55?"',
            ],
            'outcome' =>
                'A year-by-year view of net worth, cash flow, tax and FIRE progress under three market regimes — not just a single optimistic headline number.',
        ],
        [
            'slug' => 'property',
            'badge' => 'Property investor',
            'title' => 'Running a property portfolio',
            'audience' => 'Private landlords and property investors with primary home plus one or more rentals.',
            'problem' =>
                'Tracking net yield after municipal property tax, fortune tax on real-estate value, rental tax, deductible interest and eventual capital-gains tax on sale is tedious — and changes every year the mortgage amortises.',
            'how' => [
                'Add each property as its own asset with market value, mortgage, rental income and maintenance.',
                'Apply the correct municipal property-tax configuration (327 Norwegian municipalities ship with the app).',
                'Let the engine compute annuity amortisation, deductible interest and rental-tax per year.',
                'Simulate selling a rental in a future year — the engine applies realisation tax and transfers the net into another asset.',
                'Compare keeping vs. selling across pessimistic, realistic and optimistic scenarios.',
            ],
            'outcome' =>
                'Clear visibility into whether each property earns its keep on an after-tax basis, and a defensible plan for when to sell or refinance.',
        ],
        [
            'slug' => 'one-person-as',
            'badge' => 'One-person AS',
            'title' => 'Extracting value from a limited company',
            'audience' =>
                'Consultants and founders running a Norwegian AS who need to plan salary vs. dividend vs. retained earnings.',
            'problem' =>
                'You can pay yourself salary (taxed as income), dividend (company tax, then dividend tax on the net, with a shielding deduction), or build up retained earnings. The trade-offs compound over decades.',
            'how' => [
                'Model the company as a separate asset group with its own cash flow and fortune-tax valuation.',
                'Add salary rules that transfer from company to private with correct income-tax brackets.',
                'Add dividend rules — the engine applies 22% company tax first, then dividend tax on the net above the tax shield.',
                'Simulate a "take over as private" event in a future year and see the full realisation-plus-dividend stack.',
                'Compare strategies side by side: all-salary, all-dividend, mixed with retained earnings.',
            ],
            'outcome' =>
                'A 20-year projection showing which extraction strategy leaves you with the most after-tax wealth — not just this year but every year.',
        ],
        [
            'slug' => 'retirement-timing',
            'badge' => 'Retirement timing',
            'title' => 'Early, normal or delayed retirement',
            'audience' => 'Anyone within ten years of retirement wondering which year actually makes the most sense.',
            'problem' =>
                'Folketrygden, AFP, tjenestepensjon and private savings all kick in on different dates and are taxed differently. Small changes in when you start each of them can move lifetime net worth by six figures.',
            'how' => [
                'Set three different wished retirement years and run three parallel configurations.',
                'Let the engine sequence public pension, OTP and private savings automatically based on configured start years.',
                'Watch cash-flow and net-worth curves for each scenario on the same axis.',
                'Spot the year where delayed retirement stops being worth it — usually when health or time become the binding constraint.',
            ],
            'outcome' =>
                'A direct, numeric answer to the question "how much does it cost me to retire three years earlier?"',
        ],
        [
            'slug' => 'inheritance',
            'badge' => 'Household planning',
            'title' => 'Child costs, barnetrygd and inheritance',
            'audience' => 'Households with children at home or a known future inheritance event.',
            'problem' =>
                'Kids are negative cash flow until they move out; then they are not. Inheritance lands in a future year with its own tax treatment. Both events distort long-term plans if modelled as a flat average.',
            'how' => [
                'Add each child as an asset with income (barnetrygd), expenses, and a "removed from economy" year.',
                'Model an inheritance event in a future year with the expected value and tax treatment.',
                'Let the engine compute before/after cash-flow and net-worth changes automatically.',
                'Scenario-test what happens if the inheritance is delayed or reduced.',
            ],
            'outcome' =>
                'An honest picture of your economy through and beyond the child-raising years — and a plan that does not fall over when the timeline shifts.',
        ],
        [
            'slug' => 'advisors',
            'badge' => 'Advisors',
            'title' => 'Advising multiple clients',
            'audience' => 'Independent financial advisors, accountants and family-office operators.',
            'problem' =>
                'Each client has a different portfolio, tax situation and timeline. Keeping spreadsheets per client is fragile and slow to update when tax rules change.',
            'how' => [
                'One team per client — data is fully isolated via multi-tenant team scoping.',
                'Shared change-rate configurations so assumptions are consistent across your book.',
                'Export the year-by-year Excel to send to the client after each meeting.',
                'Use the AI assistant in Norwegian or English to make quick configuration tweaks live.',
            ],
            'outcome' =>
                'A single system to maintain your assumptions, run every client\'s simulation in minutes, and deliver a professional exportable document.',
        ],
    ];
@endphp


@section('content')
    <section class="relative hero-gradient overflow-hidden">
        <div class="absolute inset-0 grid-pattern opacity-40"></div>
        <div class="relative max-w-5xl mx-auto px-6 lg:px-8 pt-20 pb-16 sm:pt-28 sm:pb-20 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                Use cases
            </span>
            <h1 class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                One engine,<br>many financial lives.
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                Whether you are chasing early retirement, running a property portfolio, or extracting value from a
                one-person AS — Wealth Prognosis models the full picture, year by year, after tax.
            </p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-12">
            <nav aria-label="Use cases" class="flex flex-wrap gap-2">
                @foreach ($cases as $case)
                    <a href="#{{ $case['slug'] }}"
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.03] hover:border-brand-400/40 hover:bg-brand-500/10 px-4 py-2 text-sm text-slate-200 hover:text-white transition">
                        {{ $case['badge'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 pb-20 sm:pb-28 space-y-16">
            @foreach ($cases as $case)
                <article id="{{ $case['slug'] }}" class="scroll-mt-24">
                    <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-6 sm:p-10">
                        <div class="flex flex-wrap items-center gap-3">
                            <span
                                class="inline-flex items-center rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                                {{ $case['badge'] }}
                            </span>
                            <span class="text-xs text-slate-400">{{ $case['audience'] }}</span>
                        </div>
                        <h2 class="mt-4 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                            {{ $case['title'] }}</h2>

                        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="rounded-xl border border-white/5 bg-slate-950/40 p-5">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">The problem
                                </h3>
                                <p class="mt-3 text-sm text-slate-200 leading-relaxed">{{ $case['problem'] }}</p>
                            </div>
                            <div class="rounded-xl border border-white/5 bg-slate-950/40 p-5 md:col-span-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">How Wealth
                                    Prognosis handles it</h3>
                                <ul class="mt-3 space-y-2">
                                    @foreach ($case['how'] as $step)
                                        <li class="flex gap-3 text-sm text-slate-200 leading-relaxed">
                                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-400"></span>
                                            <span>{{ $step }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="mt-6 rounded-xl border border-brand-400/15 bg-brand-500/5 p-5">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-brand-300">Outcome</h3>
                            <p class="mt-2 text-sm sm:text-base text-slate-100 leading-relaxed">{{ $case['outcome'] }}
                            </p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-white">Your case isn't on this list?</h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">The engine is configuration-driven. If you can
                describe an asset, income stream or tax event, you can model it.</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/admin') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    Try it with your own data
                </a>
                <a href="{{ route('faq') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    Read the FAQ
                </a>
            </div>
        </div>
    </section>
@endsection

@push('head')
    @php
        $useCasesSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'CollectionPage',
                    '@id' => url()->current() . '#use-cases',
                    'name' => 'Wealth Prognosis use cases',
                    'url' => url()->current(),
                    'inLanguage' => app()->getLocale(),
                    'isPartOf' => ['@id' => url('/') . '#website'],
                    'hasPart' => array_map(
                        fn(array $c): array => [
                            '@type' => 'Article',
                            'headline' => $c['title'],
                            'about' => $c['badge'],
                            'description' => $c['problem'],
                            'url' => route('use-cases') . '#' . $c['slug'],
                        ],
                        $cases,
                    ),
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Use cases', 'item' => route('use-cases')],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($useCasesSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
