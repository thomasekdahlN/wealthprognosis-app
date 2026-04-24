@extends('layouts.public')

@section('title', 'Glossary — FIRE, taxation and wealth planning terms')
@section('description',
    'Plain-language definitions of every financial, tax and simulation term used inside Wealth
    Prognosis — FIRE, crossover point, safe withdrawal rate, Norwegian fortune tax, tax shield, rule engine syntax
    and more.')
@section('og_type', 'article')

@php
    $groups = [
        [
            'slug' => 'fire',
            'title' => 'Financial independence &amp; retirement',
            'intro' =>
                'The core concepts behind F.I.R.E (Financial Independence, Retire Early) and how Wealth Prognosis models them.',
            'terms' => [
                [
                    'term' => 'FIRE (Financial Independence, Retire Early)',
                    'def' => 'The point at which your passive income or invested wealth is large enough that you no longer need employment income to
cover your living expenses. Wealth Prognosis calculates your FIRE year across three scenarios.',
                ],
                [
                    'term' => 'FIRE number',
                    'def' => 'The total invested wealth needed to sustain your lifestyle indefinitely, traditionally estimated as 25 × annual
expenses (the inverse of a 4% withdrawal rate). Wealth Prognosis displays this number and tracks your progress toward
it.',
                ],
                [
                    'term' => 'Crossover point',
                    'def' => 'The moment when your passive investment income exceeds your annual expenses. After crossover you can, in principle,
stop working without depleting your capital.',
                ],
                [
                    'term' => 'Safe Withdrawal Rate (SWR)',
                    'def' => 'The percentage of your portfolio you can withdraw each year in retirement without running out of money over a long
horizon. The 4% rule is a starting heuristic; Wealth Prognosis instead runs a full year-by-year sell-down simulation
against your actual assets and taxes.',
                ],
                [
                    'term' => 'Liquidation strategy',
                    'def' => 'Instead of the static 4% rule, Wealth Prognosis liquidates your liquid assets down to zero between your wished
retirement year and your expected death year. Non-liquid assets you want to keep (house, cabin, car, boat) are
preserved.',
                ],
                [
                    'term' => 'Liquid vs. non-liquid assets',
                    'def' => 'Liquid assets (stocks, funds, bank, crypto) can be sold gradually to fund retirement. Non-liquid assets (property,
vehicles, jewelry) are kept until death. The classification drives which assets participate in the FIRE sell-down.',
                ],
                [
                    'term' => 'Three-scenario projection',
                    'def' => 'Every simulation is run three times — pessimistic, realistic and optimistic — using different change rates. You see the
full range of outcomes side by side instead of one optimistic headline number.',
                ],
                [
                    'term' => 'Sequence of returns risk (SoRR)',
                    'def' => 'The risk that poor investment returns early in retirement do disproportionate damage, because you are withdrawing from
a shrinking portfolio at the same time. Two retirees with identical average returns can end up with very different
outcomes depending on the <em>order</em> in which those returns arrive. Simulations let you stress-test your
drawdown plan against a bad early sequence.',
                ],
                [
                    'term' => 'Drawdown',
                    'def' => 'The peak-to-trough decline of a portfolio or asset over a given period, usually expressed as a percentage. A 30%
drawdown means the value dropped 30% from its highest point before recovering. Small drawdowns during accumulation
are tolerable; large drawdowns close to or inside retirement are the main trigger for SoRR and plan failure.',
                ],
                [
                    'term' => 'Retirement readiness',
                    'def' => 'How close you are to retirement capital adequacy. Wealth Prognosis projects net worth forward at a configurable growth
rate and compares against a target ≈ 25 × 80% of current expenses.',
                ],
            ],
        ],
        [
            'slug' => 'taxation',
            'title' => 'Taxation',
            'intro' =>
                'The Norwegian tax rules modelled by the engine. Each is computed per year, per asset, with correct brackets.',
            'terms' => [
                [
                    'term' => 'Fortune tax (formueskatt)',
                    'def' => 'An annual wealth tax on net assets above a threshold, split between state and municipality. Different asset classes get
different valuation discounts (for example primary residence and operating company shares).',
                ],
                [
                    'term' => 'Property tax (eiendomsskatt)',
                    'def' => 'A municipal tax on real estate, with rates and thresholds that differ by municipality. Wealth Prognosis ships with a
catalogue of 327 municipal property-tax configurations.',
                ],
                [
                    'term' => 'Income tax',
                    'def' => 'Personal tax on salary, pension and ordinary income. Modelled with bracket tax (trinnskatt), ordinary income tax and a
simplified personfradrag.',
                ],
                [
                    'term' => 'Capital-gains tax',
                    'def' => 'Tax on realised gains when you sell shares, funds or property. The engine applies this at the moment of sale, transfer
or liquidation — not on paper gains.',
                ],
                [
                    'term' => 'Pension tax',
                    'def' => 'Tax applied to pension income (offentlig pensjon, tjenestepensjon, IPS) once payouts start, using the appropriate
bracket and rate for that income class.',
                ],
                [
                    'term' => 'Rental tax',
                    'def' =>
                        'Tax on net rental income from rental properties, after deductible costs such as mortgage interest and maintenance.',
                ],
                [
                    'term' => 'Company tax',
                    'def' => 'Corporate income tax (currently 22% in Norway) applied to profits inside a company before any distribution to
private.',
                ],
                [
                    'term' => 'Dividend tax',
                    'def' => 'Tax on dividends distributed from company to private, grossed up and taxed above the shielding deduction
(skjermingsfradrag)
.',
                ],
                [
                    'term' => 'Tax shield (skjermingsfradrag)',
                    'def' => 'A deduction against share dividends and gains equal to the risk-free rate × the share cost basis. Wealth Prognosis
calculates and carries the shielding balance year by year.',
                ],
                [
                    'term' => 'Fritaksmetoden',
                    'def' => 'The Norwegian participation-exemption rule: a company can sell qualifying shares tax-free, but a private investor is
taxed on the same gain. The engine respects which owner holds the asset.',
                ],
                [
                    'term' => 'Company-to-private transfer',
                    'def' => 'When value is moved from a company to a private person, two tax layers apply: realisation tax inside the company, then
dividend tax on the net amount transferred. Wealth Prognosis models both correctly.',
                ],
                [
                    'term' => 'Max-loan capacity',
                    'def' => 'An estimate of how much mortgage you could carry given your income, other debt and interest rate. Useful for planning
the next property purchase.',
                ],
            ],
        ],
        [
            'slug' => 'simulation',
            'title' => 'Simulation &amp; cash flow',
            'intro' =>
                'How the simulation engine projects your economy forward — the building blocks of every yearly result.',
            'terms' => [
                [
                    'term' => 'Asset configuration',
                    'def' => 'A named collection of assets, income, expenses and mortgages representing a person, a household or a company — the
input to a simulation.',
                ],
                [
                    'term' => 'Asset year',
                    'def' => 'The state of a single asset in a single year: market value, income, expenses, mortgage balance, tax charges and cash
flow. The core unit of both actuals and simulation output.',
                ],
                [
                    'term' => 'Change rate',
                    'def' => 'An annual percentage (or fixed amount) that drives how a value evolves year on year — for example +3% equity growth,
−10% car depreciation, +4% wage growth. Change rates can differ per year and per scenario.',
                ],
                [
                    'term' => 'Prognosis',
                    'def' => 'A named set of change rates used to roll forward every asset for every year. Built-ins include realistic, positive,
negative, tenpercent, zero and variable.',
                ],
                [
                    'term' => 'Cash flow',
                    'def' => 'Income minus expenses for a year, after tax. Can be positive (surplus to reinvest or pay down debt) or negative
(drawing from capital).',
                ],
                [
                    'term' => 'Net worth',
                    'def' => 'Total asset market value minus total liabilities (mortgages, other debt). Displayed on the Actual Assets Dashboard and
tracked year by year.',
                ],
                [
                    'term' => 'CAGR',
                    'def' => 'Compound Annual Growth Rate — a smoothed average yearly growth between a start value and an end value: (end / start)^(1
/ years) − 1. Used in simulation summaries.',
                ],
                [
                    'term' => 'Expense ratio',
                    'def' => 'Annual expenses divided by annual income, expressed as a percentage. A quick read on how much headroom your current
plan has.',
                ],
                [
                    'term' => 'KPI (Consumer Price Index)',
                    'def' => 'The yearly price-level index used as a reference for inflation adjustments on expenses, tax thresholds and pension
values.',
                ],
                [
                    'term' => 'Annuity mortgage',
                    'def' => 'A loan with equal total payments per period, where the interest portion shrinks and the principal portion grows over
time. The default mortgage model in Wealth Prognosis.',
                ],
                [
                    'term' => 'Extra downpayment',
                    'def' => 'An additional principal payment on top of the regular annuity schedule, triggered by a rule. It reduces future interest
and recomputes the remaining mortgage automatically.',
                ],
                [
                    'term' => 'Audit stamping',
                    'def' => 'Every record stores created_by, updated_by, created_at, updated_at plus SHA-256 checksums of its content at create- and
update-time, for traceability and tamper detection.',
                ],
            ],
        ],
        [
            'slug' => 'rule-engine',
            'title' => 'Rule engine syntax',
            'intro' => 'The compact rule grammar used on every asset to describe yearly changes, transfers and sources. All examples are valid
configuration values.',
            'terms' => [
                [
                    'term' => '+10% / −10% / 10%',
                    'def' => 'Adds, subtracts, or computes a percentage of the current amount. +10% grows the value by 10%; 10% just returns 10% of
it without mutating.',
                ],
                [
                    'term' => '+1000 / −1000',
                    'def' => 'Adds or subtracts a fixed amount. Useful for explicit top-ups or drawdowns — for example pension contributions or child
costs.',
                ],
                [
                    'term' => '+1/10, −1/10, 1/10',
                    'def' => 'Works with a fixed divisor: adds, subtracts or computes one tenth of the current amount every year. The divisor does
not change over time.',
                ],
                [
                    'term' => '+1|10, −1|10, 1|10',
                    'def' => 'Works with a decreasing divisor: the first year uses 1/10, the next 1/9, then 1/8, and so on. Perfect for emptying a
fund evenly over exactly 10 years.',
                ],
                [
                    'term' => 'source',
                    'def' => 'Pulls a value from another asset without reducing that asset. Example: take 5% of salary and add it to OTP — salary is
untouched, OTP grows.',
                ],
                [
                    'term' => 'transfer',
                    'def' => 'Moves value from the current asset to another. Applies the correct realisation tax first when selling from a taxed
asset. Transfers must target a later asset in the config (except extraDownPayment, which targets an earlier one).',
                ],
                [
                    'term' => 'Reserved asset names',
                    'def' => '<code class="text-brand-300">total</code> is the sum of everything, <code class="text-brand-300">company</code> the
company sub-total, <code class="text-brand-300">private</code> the private sub-total, and <code
    class="text-brand-300">income</code> collects all taxed private income (not only salary).',
                ],
            ],
        ],
    ];
@endphp

@section('content')
    <section class="relative hero-gradient overflow-hidden">
        <div class="absolute inset-0 grid-pattern opacity-40"></div>
        <div class="relative max-w-5xl mx-auto px-6 lg:px-8 pt-20 pb-16 sm:pt-28 sm:pb-20 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                Glossary
            </span>
            <h1 class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                Every term,<br>explained plainly.
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                The exact vocabulary used by the Wealth Prognosis engine — from FIRE and crossover point to Norwegian
                fortune tax and rule-engine syntax. Written for humans, not accountants.
            </p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-12">
            <nav aria-label="Glossary sections" class="flex flex-wrap gap-2">
                @foreach ($groups as $group)
                    <a href="#{{ $group['slug'] }}"
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.03] hover:border-brand-400/40 hover:bg-brand-500/10 px-4 py-2 text-sm text-slate-200 hover:text-white transition">
                        {!! $group['title'] !!}
                    </a>
                @endforeach
            </nav>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 pb-20 sm:pb-28 space-y-20">
            @foreach ($groups as $group)
                <div id="{{ $group['slug'] }}" class="scroll-mt-24">
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">{!! $group['title'] !!}</h2>
                    <p class="mt-3 text-base text-slate-300 max-w-3xl">{{ $group['intro'] }}</p>
                    <dl class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($group['terms'] as $term)
                            <div
                                class="rounded-2xl border border-white/5 bg-white/[0.02] p-6 hover:border-brand-400/30 transition">
                                <dt class="text-base font-semibold text-white">{!! $term['term'] !!}</dt>
                                <dd class="mt-2 text-sm text-slate-300 leading-relaxed">{!! $term['def'] !!}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-white">See the terms in action.</h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">Open the dashboard to watch FIRE numbers, tax
                calculations and cash flow update for your own assets.</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/admin') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    Open dashboard
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
        $definedTerms = [];
        foreach ($groups as $group) {
            foreach ($group['terms'] as $term) {
                $definedTerms[] = [
                    '@type' => 'DefinedTerm',
                    'name' => trim(html_entity_decode(strip_tags($term['term']))),
                    'description' => trim(html_entity_decode(strip_tags($term['def']))),
                    'inDefinedTermSet' => url()->current() . '#glossary',
                ];
            }
        }
        $glossarySchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'DefinedTermSet',
                    '@id' => url()->current() . '#glossary',
                    'name' => 'Wealth Prognosis Glossary',
                    'inLanguage' => app()->getLocale(),
                    'isPartOf' => ['@id' => url('/') . '#website'],
                    'hasDefinedTerm' => $definedTerms,
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Glossary', 'item' => route('glossary')],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($glossarySchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
