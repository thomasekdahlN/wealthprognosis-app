@extends('layouts.public')

@section('title', 'Methodology — how every advanced widget is calculated')
@section('description',
    'The exact mathematics behind every advanced widget in Wealth Prognosis — FIRE progress, crossover
    point, safe withdrawal rate, retirement readiness, savings rate, net worth projection and taxation
    — written as LaTeX formulas with worked examples.')
@section('og_type', 'article')

@php
    $widgets = [
        [
            'slug' => 'net-worth',
            'title' => 'Net worth over time',
            'lede' =>
                'The headline number. Market value of everything you own, minus everything you owe, charted year by year.',
            'inputs' => ['asset_market_amount (per asset, per year)', 'mortgage_amount (per asset, per year)'],
            'formula' => '\[ NW_y \;=\; \sum_{a \in A_y} M_{a,y} \;-\; \sum_{a \in A_y} L_{a,y} \]',
            'legend' =>
                '\(NW_y\) net worth in year \(y\), \(M_{a,y}\) market value of asset \(a\), \(L_{a,y}\) mortgage balance, \(A_y\) all active assets for the chosen configuration.',
            'example' =>
                'Year 2024 — assets total 8 450 000, mortgages total 3 200 000. \(NW_{2024} = 8\,450\,000 - 3\,200\,000 = 5\,250\,000\).',
            'caveats' =>
                'Uses your actual entered values up to the current year; future years come from the prognosis engine, not this widget.',
        ],
        [
            'slug' => 'fire-number',
            'title' => 'FIRE number &amp; progress',
            'lede' =>
                'How much wealth you need to retire — and how far you already are. The single most important number on the dashboard.',
            'inputs' => [
                'expence_amount (all assets, current year)',
                'asset_market_amount (liquid + preserved, current year)',
            ],
            'formula' => '\[ F \;=\; 25 \times E \qquad\quad p \;=\; \min\!\left(\tfrac{P}{F},\, 1\right) \]',
            'legend' =>
                '\(F\) FIRE number, \(E\) annual expenses, \(P\) current portfolio value, \(p\) progress toward FIRE (0 to 1).',
            'example' =>
                'Annual expenses 540 000, portfolio 7 200 000. \(F = 25 \times 540\,000 = 13\,500\,000\); \(p = 7\,200\,000 / 13\,500\,000 \approx 53.3\%\).',
            'caveats' =>
                'The 25× multiplier is the inverse of the 4% safe-withdrawal rule. For a more conservative plan, raise the multiplier (30× ⇒ 3.33% SWR).',
        ],
        [
            'slug' => 'fire-crossover',
            'title' => 'FIRE crossover point',
            'lede' =>
                'The moment your portfolio can pay for your life from passive income alone. After this you are, in principle, free.',
            'inputs' => ['asset_market_amount (current year)', 'expence_amount (current year)'],
            'formula' => '\[ \text{crossover} \;\Longleftrightarrow\; 0.04 \cdot P \;\geq\; E \]',
            'legend' =>
                '\(P\) current portfolio, \(E\) annual expenses. The 0.04 constant is the classic 4% safe-withdrawal rate.',
            'example' =>
                'Portfolio 15 000 000, expenses 540 000. Passive income \(0.04 \times 15\,000\,000 = 600\,000 \geq 540\,000\) ⇒ crossover achieved.',
            'caveats' =>
                'Binary indicator — not a sell-down simulation. For year-by-year withdrawal feasibility across three scenarios, the full prognosis engine runs a real liquidation against your actual assets and taxes.',
        ],
        [
            'slug' => 'fire-metrics',
            'title' => 'FIRE metrics over 30 years',
            'lede' =>
                'Projects net worth forward 30 years against an inflation-adjusted FIRE target, so you can see the year the two lines cross.',
            'inputs' => [
                'current net worth',
                'annual savings (\(I - E\))',
                'growth rate \(r = 7\%\)',
                'inflation \(\pi = 3\%\)',
            ],
            'formula' => '\[ P_{t+1} \;=\; (P_t + S)(1 + r), \qquad F_t \;=\; F_0 \cdot (1 + \pi)^t \]',
            'legend' =>
                '\(P_t\) projected portfolio in year \(t\), \(S\) annual savings (income minus expenses), \(r\) nominal growth rate, \(F_t\) FIRE number inflated from \(F_0\) by \(\pi\).',
            'example' =>
                'Start \(P_0 = 5\,000\,000\), \(S = 300\,000\). After one year \(P_1 = (5\,000\,000 + 300\,000) \times 1.07 = 5\,671\,000\). After ten years \(P_{10} \approx 14\,020\,000\).',
            'caveats' =>
                'Uses constant \(r\) and \(\pi\) for readability. The main prognosis engine runs the same projection per asset, per year, across three scenarios with configurable change rates.',
        ],
        [
            'slug' => 'savings-rate',
            'title' => 'Savings rate over time',
            'lede' =>
                'The single best predictor of your FIRE timeline. A 50% savings rate brings financial independence in roughly 17 years regardless of income.',
            'inputs' => ['income_amount (per year, income assets)', 'expence_amount (per year, all assets)'],
            'formula' => '\[ s_y \;=\; \frac{I_y - E_y}{I_y} \]',
            'legend' =>
                '\(s_y\) savings rate in year \(y\), \(I_y\) total income, \(E_y\) total expenses. Expressed as a percentage. Benchmark line drawn at 20%.',
            'example' => 'Income 900 000, expenses 540 000. \(s = (900\,000 - 540\,000) / 900\,000 = 40\%\).',
            'caveats' =>
                'Historic years only — the widget never projects into the future. Negative when expenses exceed income (drawing down).',
        ],
        [
            'slug' => 'retirement-readiness',
            'title' => 'Retirement readiness',
            'lede' =>
                'Projects today\'s net worth to your planned retirement age against a capital-adequacy target, using your own expense baseline.',
            'inputs' => [
                'current net worth',
                'birth_year, pension_wish_year, death_year',
                'annual income, annual expenses',
            ],
            'formula' => '\[ T \;=\; 25 \times 0.80 \times E \qquad\quad NW_{t} \;=\; (NW_{t-1} + S)(1+r) \]',
            'legend' =>
                '\(T\) retirement target (25× of 80% of current expenses — the classic 70–80% income-replacement rule), \(NW_t\) projected net worth at age \(t\), \(r\) assumed growth (default 7%).',
            'example' =>
                'Expenses 540 000 ⇒ \(T = 25 \times 0.80 \times 540\,000 = 10\,800\,000\). Starting from 3 000 000 at age 40 with 300 000 annual savings, \(NW_{65} \approx 23\,100\,000\) — comfortably above target.',
            'caveats' =>
                'The 80% replacement ratio is a widely used rule of thumb, not a personal forecast. Pension payouts from tjenestepensjon/IPS/offentlig pensjon are modelled separately by the tax engine.',
        ],
        [
            'slug' => 'actual-tax-rate',
            'title' => 'Actual effective tax rate',
            'lede' =>
                'Your real tax burden — every tax the engine calculated, divided by taxable base. Not a headline rate, the rate you actually pay.',
            'inputs' => ['income_tax', 'fortune_tax', 'property_tax', 'capital_gains_tax', 'taxable_income_base'],
            'formula' =>
                '\[ \tau_y \;=\; \frac{T^{\text{income}}_y + T^{\text{fortune}}_y + T^{\text{property}}_y + T^{\text{gains}}_y}{B_y} \]',
            'legend' =>
                '\(\tau_y\) effective tax rate in year \(y\), \(T^{\star}_y\) the tax paid of each kind, \(B_y\) the taxable base (gross income + realised gains).',
            'example' => 'Gross base 950 000, total taxes 278 400. \(\tau = 278\,400 / 950\,000 \approx 29.3\%\).',
            'caveats' =>
                'Fortune tax and property tax are wealth-based but are included in the numerator because they are a real cash outflow. The ratio is not directly comparable to a marginal income-tax rate.',
        ],
    ];

    $taxFormulas = [
        [
            'title' => 'Fortune tax (formueskatt)',
            'formula' =>
                '\[ T^{\text{fortune}} \;=\; \max\!\bigl(0,\; W_{\text{net}} - W_{\text{threshold}}\bigr) \cdot \bigl(r_{\text{state}} + r_{\text{muni}}\bigr) \]',
            'legend' =>
                '\(W_{\text{net}}\) valued net wealth (primary residence, shares, business assets each get their own valuation discount), \(W_{\text{threshold}}\) annual threshold, \(r_{\text{state}}\) and \(r_{\text{muni}}\) state and municipal rates.',
        ],
        [
            'title' => 'Bracket tax (trinnskatt)',
            'formula' =>
                '\[ T^{\text{bracket}} \;=\; \sum_{k=1}^{K} r_k \cdot \max\!\bigl(0,\; \min(Y, b_{k+1}) - b_k\bigr) \]',
            'legend' =>
                '\(Y\) gross ordinary income, \(b_k\) lower bound of bracket \(k\), \(r_k\) marginal rate for that bracket. Brackets are loaded per year from the tax configuration.',
        ],
        [
            'title' => 'Tax shield (skjermingsfradrag)',
            'formula' =>
                '\[ S_t \;=\; C_t \cdot r^{\text{skjerm}}_t, \qquad T^{\text{dividend}} \;=\; \max\!\bigl(0,\; D_t - S_t\bigr) \cdot g \cdot r^{\text{cap}} \]',
            'legend' =>
                '\(C_t\) cost basis, \(r^{\text{skjerm}}_t\) annual risk-free shielding rate, \(S_t\) shielding deduction, \(D_t\) dividend received, \(g\) gross-up factor (currently 1.72), \(r^{\text{cap}}\) capital tax rate.',
        ],
        [
            'title' => 'Property tax (eiendomsskatt)',
            'formula' =>
                '\[ T^{\text{property}} \;=\; \max\!\bigl(0,\; V \cdot d - V_{\text{threshold}}\bigr) \cdot r_{\text{muni}} \]',
            'legend' =>
                '\(V\) property market value, \(d\) municipal valuation discount (often 0.70), \(V_{\text{threshold}}\) municipal bottom deduction, \(r_{\text{muni}}\) municipal rate. 327 municipalities ship configured.',
        ],
    ];

    $prognosis = [
        [
            'title' => 'Yearly compound roll-forward',
            'formula' => '\[ V_{y+1} \;=\; V_y \cdot \bigl(1 + c_{y,s}\bigr) \;+\; \Delta_{y,s} \]',
            'legend' =>
                'Each asset evolves year by year. \(V_y\) value in year \(y\), \(c_{y,s}\) percentage change rate for year \(y\) under scenario \(s\), \(\Delta_{y,s}\) fixed-amount adjustments (top-ups, transfers, rule-engine mutations).',
        ],
        [
            'title' => 'Compound Annual Growth Rate (CAGR)',
            'formula' => '\[ \text{CAGR} \;=\; \left(\frac{V_{\text{end}}}{V_{\text{start}}}\right)^{\!1/n} - 1 \]',
            'legend' =>
                'Smoothed annualised growth between two points in time. \(n\) is the number of years. Used in simulation summaries and the asset overview card.',
        ],
        [
            'title' => 'Real vs. nominal return',
            'formula' => '\[ r_{\text{real}} \;=\; \frac{1 + r_{\text{nominal}}}{1 + \pi} - 1 \]',
            'legend' =>
                'Converts a nominal return into real (inflation-adjusted) return using CPI \(\pi\). The engine shows both; expenses and tax thresholds are inflated using the same \(\pi\).',
        ],
        [
            'title' => 'Annuity mortgage payment',
            'formula' => '\[ A \;=\; L \cdot \frac{r\,(1+r)^{n}}{(1+r)^{n} - 1} \]',
            'legend' =>
                '\(A\) annual payment, \(L\) remaining loan, \(r\) periodic interest rate, \(n\) remaining term. Every year splits into interest (tax-deductible) and principal.',
        ],
    ];
@endphp

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css"
        integrity="sha384-nB0miv6/jRmo5UMMR1wu3Gz6NLsoTkbqJghGIsx//Rlm+ZU03BU6SQNC66uf4l5+" crossorigin="anonymous">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"
        integrity="sha384-7zkQWkzuo3B5mTepMUcHkMB5jZaolc2xDwL6VFqjFALcbeS9Ggm/Yr2r3Dy4lfFg" crossorigin="anonymous">
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"
        integrity="sha384-43gviWU0YVjaDtb/GhzOouOXtZMP/7XUzwPTstBeZFe/+rCMvRwr4yROQP43s0Xk" crossorigin="anonymous"
        onload="renderMathInElement(document.body,{delimiters:[{left:'\\[',right:'\\]',display:true},{left:'\\(',right:'\\)',display:false}],throwOnError:false});">
    </script>
    <style>
        .katex {
            font-size: 1.05em;
            color: #e2e8f0;
        }

        .katex-display {
            margin: 0;
            padding: 1rem 0;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .katex-display>.katex {
            color: #f1f5f9;
        }
    </style>
@endpush

@section('content')
    <section class="relative hero-gradient overflow-hidden">
        <div class="absolute inset-0 grid-pattern opacity-40"></div>
        <div class="relative max-w-5xl mx-auto px-6 lg:px-8 pt-20 pb-16 sm:pt-28 sm:pb-20 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                Methodology
            </span>
            <h1 class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                The math behind<br>every number.
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                No black boxes. Every advanced widget in Wealth Prognosis is documented here with its exact
                formula, inputs and a worked example — so you can trust the output, audit it, and reproduce it.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#widgets"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    Jump to the formulas
                </a>
                <a href="{{ route('glossary') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    Read the glossary
                </a>
            </div>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-16 grid md:grid-cols-3 gap-6">
            <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                <h3 class="text-sm font-semibold text-brand-300">Three scenarios, always</h3>
                <p class="mt-2 text-sm text-slate-300 leading-relaxed">Every formula runs three times —
                    pessimistic, realistic, optimistic — using different change rates. You see the full range
                    of outcomes side by side.</p>
            </div>
            <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                <h3 class="text-sm font-semibold text-brand-300">Year-by-year, not averages</h3>
                <p class="mt-2 text-sm text-slate-300 leading-relaxed">Taxes, expenses and returns are
                    computed for each year individually. No smoothing, no single headline number hiding the
                    truth.</p>
            </div>
            <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                <h3 class="text-sm font-semibold text-brand-300">Auditable by design</h3>
                <p class="mt-2 text-sm text-slate-300 leading-relaxed">Every calculated row carries a
                    SHA-256 checksum plus created/updated stamps. You can export the full workings to Excel
                    or JSON at any time.</p>
            </div>
        </div>
    </section>

    <section id="widgets" class="relative border-t border-white/5 bg-slate-950 scroll-mt-24">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20">
            <div class="max-w-2xl">
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">Advanced widgets</h2>
                <p class="mt-4 text-base text-slate-300 leading-relaxed">The widgets that do real
                    mathematics — not just sums and group-bys. Each one lists its data inputs, the exact
                    formula, a worked example, and the caveats we want you to know.</p>
            </div>

            <nav aria-label="Widgets" class="mt-10 flex flex-wrap gap-2">
                @foreach ($widgets as $w)
                    <a href="#{{ $w['slug'] }}"
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.03] hover:border-brand-400/40 hover:bg-brand-500/10 px-4 py-2 text-sm text-slate-200 hover:text-white transition">
                        {!! $w['title'] !!}
                    </a>
                @endforeach
            </nav>

            <div class="mt-14 space-y-10">
                @foreach ($widgets as $w)
                    <article id="{{ $w['slug'] }}"
                        class="scroll-mt-24 rounded-3xl border border-white/5 bg-white/[0.02] p-8 sm:p-10 hover:border-brand-400/30 transition">
                        <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">{!! $w['title'] !!}</h3>
                        <p class="mt-3 text-base text-slate-300 leading-relaxed max-w-3xl">{!! $w['lede'] !!}</p>

                        <div class="mt-6 grid md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-xs font-semibold text-brand-300 uppercase tracking-wider">Inputs</h4>
                                <ul class="mt-2 space-y-1 text-sm text-slate-300">
                                    @foreach ($w['inputs'] as $input)
                                        <li class="flex gap-2">
                                            <span class="text-brand-400">•</span>
                                            <code class="text-slate-200">{!! $input !!}</code>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div>
                                <h4 class="text-xs font-semibold text-brand-300 uppercase tracking-wider">Caveats</h4>
                                <p class="mt-2 text-sm text-slate-300 leading-relaxed">{!! $w['caveats'] !!}</p>
                            </div>
                        </div>

                        <div class="mt-8 rounded-2xl border border-brand-400/20 bg-slate-900/60 p-6 sm:p-8">
                            <h4 class="text-xs font-semibold text-brand-300 uppercase tracking-wider">Formula</h4>
                            <div class="mt-3 text-slate-100">{!! $w['formula'] !!}</div>
                            <p class="mt-4 text-xs text-slate-400 leading-relaxed">{!! $w['legend'] !!}</p>
                        </div>

                        <div class="mt-6 rounded-2xl border border-white/5 bg-slate-900/30 p-6">
                            <h4 class="text-xs font-semibold text-brand-300 uppercase tracking-wider">Worked example</h4>
                            <p class="mt-2 text-sm text-slate-200 leading-relaxed">{!! $w['example'] !!}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="taxation" class="relative border-t border-white/5 bg-slate-950 scroll-mt-24">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20">
            <div class="max-w-2xl">
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">Taxation</h2>
                <p class="mt-4 text-base text-slate-300 leading-relaxed">The tax engine models the real
                    brackets, thresholds and shielding rules — not rough percentages. Rates, bands and
                    municipal rules are loaded per year from the tax configuration tables.</p>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-2">
                @foreach ($taxFormulas as $t)
                    <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-6 sm:p-8">
                        <h3 class="text-lg font-semibold text-white">{!! $t['title'] !!}</h3>
                        <div class="mt-4 rounded-xl border border-brand-400/20 bg-slate-900/60 p-5 text-slate-100">
                            {!! $t['formula'] !!}
                        </div>
                        <p class="mt-4 text-xs text-slate-400 leading-relaxed">{!! $t['legend'] !!}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="prognosis" class="relative border-t border-white/5 bg-slate-950 scroll-mt-24">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20">
            <div class="max-w-2xl">
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">Prognosis math</h2>
                <p class="mt-4 text-base text-slate-300 leading-relaxed">The primitives that roll every
                    asset forward, year after year, across three scenarios.</p>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-2">
                @foreach ($prognosis as $p)
                    <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-6 sm:p-8">
                        <h3 class="text-lg font-semibold text-white">{!! $p['title'] !!}</h3>
                        <div class="mt-4 rounded-xl border border-brand-400/20 bg-slate-900/60 p-5 text-slate-100">
                            {!! $p['formula'] !!}
                        </div>
                        <p class="mt-4 text-xs text-slate-400 leading-relaxed">{!! $p['legend'] !!}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-white">Now run the numbers on
                your own life.</h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">Every formula on this page runs live
                against your own assets, income, taxes and scenarios the moment you sign in. No
                spreadsheet, no guesswork.</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/admin') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    Open the dashboard
                </a>
                <a href="{{ route('features') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    Browse features
                </a>
            </div>
        </div>
    </section>
@endsection

@push('head')
    @php
        $methodologySchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'TechArticle',
                    '@id' => url()->current() . '#article',
                    'headline' => 'Methodology — how every advanced widget is calculated',
                    'description' =>
                        'The exact mathematics behind every advanced widget in Wealth Prognosis, with LaTeX formulas and worked examples.',
                    'inLanguage' => app()->getLocale(),
                    'isPartOf' => ['@id' => url('/') . '#website'],
                    'about' => [
                        'FIRE number',
                        'Safe withdrawal rate',
                        'Retirement readiness',
                        'Savings rate',
                        'Fortune tax',
                        'Bracket tax',
                        'Tax shield',
                        'CAGR',
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Methodology',
                            'item' => route('methodology'),
                        ],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($methodologySchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
