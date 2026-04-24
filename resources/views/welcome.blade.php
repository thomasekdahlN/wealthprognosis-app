@extends('layouts.public')

@section('title', 'Wealth Prognosis — plan your financial future')
@section('description', 'Track every asset, run year-by-year financial prognoses until your death date, and get AI-powered insights on taxes, FIRE and cash flow.')

@php
    $heroFeatures = [
        ['title' => 'Year-by-year prognosis', 'body' => 'Simulate income, expenses, mortgages, cash flow and taxes from today until your death year — across pessimistic, realistic and optimistic scenarios.', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
        ['title' => 'Complete Norwegian tax', 'body' => 'Fortune tax, property tax, income tax, capital tax, pension tax, rental tax, company tax, dividend tax and the tax shield — all computed per year, per asset.', 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z'],
        ['title' => 'FIRE metrics', 'body' => 'Know exactly when you can retire early. Liquidate liquid assets until zero while keeping your house, cabin, boat and car.', 'icon' => 'M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z'],
        ['title' => 'AI financial assistant', 'body' => 'Ask questions in natural language — English or Norwegian. Add assets, update mortgages and get plain-language explanations powered by Google Gemini.', 'icon' => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z'],
        ['title' => 'Mortgage modelling', 'body' => 'Annuity loans, extra downpayments, tax-deductible interest and dynamic recalculation when your strategy changes.', 'icon' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75'],
        ['title' => 'Multi-tenant &amp; secure', 'body' => 'Built on Laravel 13 with team-scoped data, audit stamping and signed download URLs. Your data never mixes with another user\'s.', 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z'],
    ];
@endphp

@section('content')
<section id="overview" class="relative hero-gradient overflow-hidden">
    <div class="absolute inset-0 grid-pattern opacity-40"></div>
    <div class="relative max-w-7xl mx-auto px-6 lg:px-8 pt-20 pb-24 sm:pt-28 sm:pb-32">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-400 animate-pulse"></span>
                Open-source · Laravel 13 · AI-powered
            </span>
            <h1 class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                See your <span class="bg-gradient-to-r from-brand-300 via-brand-400 to-emerald-200 bg-clip-text text-transparent">financial future</span>, year by year.
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 leading-relaxed">
                Wealth Prognosis is a comprehensive planning system that tracks every asset you own, applies accurate Norwegian taxation, and simulates your economy from today until your death date — with AI insights that explain what the numbers actually mean for you.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4">
                <a href="{{ url('/admin') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    Start planning
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
                <a href="{{ route('features') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    Explore features
                </a>
            </div>
        </div>

        <dl class="mt-20 grid grid-cols-2 lg:grid-cols-4 gap-6 max-w-4xl">
            @foreach ([
                ['label' => 'Asset types', 'value' => '15+'],
                ['label' => 'Tax rules', 'value' => 'NO · complete'],
                ['label' => 'Simulation horizon', 'value' => 'to death year'],
                ['label' => 'Licence', 'value' => 'MIT'],
            ] as $stat)
                <div class="rounded-2xl border border-white/5 bg-white/[0.02] backdrop-blur p-5">
                    <dt class="text-xs uppercase tracking-wider text-slate-400">{{ $stat['label'] }}</dt>
                    <dd class="mt-1.5 text-xl font-bold text-white">{{ $stat['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>

<section class="relative border-t border-white/5 bg-slate-950">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 sm:py-28">
        <div class="max-w-2xl">
            <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">Everything you need to plan a life.</h2>
            <p class="mt-4 text-lg text-slate-400">From your first salary to the year you retire — model every asset, every tax, every transfer.</p>
        </div>

        <div class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($heroFeatures as $feature)
                <div class="group rounded-2xl border border-white/5 bg-gradient-to-b from-white/[0.04] to-transparent p-6 hover:border-brand-400/30 hover:bg-white/[0.06] transition">
                    <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-brand-500/10 text-brand-300 ring-1 ring-brand-400/20 group-hover:scale-110 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}"/></svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-white">{!! $feature['title'] !!}</h3>
                    <p class="mt-2 text-sm text-slate-400 leading-relaxed">{!! $feature['body'] !!}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-12 text-center">
            <a href="{{ route('features') }}" class="inline-flex items-center gap-2 text-brand-300 hover:text-brand-200 font-semibold transition">
                See all features
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>
</section>

<section id="how-it-works" class="relative border-t border-white/5 bg-gradient-to-b from-slate-950 to-slate-900/50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 sm:py-28">
        <div class="max-w-2xl">
            <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">How it works.</h2>
            <p class="mt-4 text-lg text-slate-400">Four steps from "I have some savings" to "here's exactly when I can retire".</p>
        </div>

        <ol class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ([
                ['step' => '01', 'title' => 'Configure your life', 'body' => 'Enter birth year, wished retirement year, official pension year and expected death year. This defines your simulation horizon.'],
                ['step' => '02', 'title' => 'Add your assets', 'body' => 'Houses, cabins, cars, pensions, stocks, bonds, crypto, companies — with income, expenses, mortgages and change rates per year.'],
                ['step' => '03', 'title' => 'Run the prognosis', 'body' => 'The engine computes taxes, cash flow, asset value and FIRE metrics year by year across pessimistic, realistic and optimistic scenarios.'],
                ['step' => '04', 'title' => 'Ask the AI', 'body' => 'Get plain-language explanations, what-if analysis and recommendations — or export the full simulation to Excel.'],
            ] as $step)
                <li class="relative rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                    <div class="text-xs font-mono text-brand-400 tracking-widest">{{ $step['step'] }}</div>
                    <h3 class="mt-3 text-lg font-semibold text-white">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm text-slate-400 leading-relaxed">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

<section class="relative border-t border-white/5 bg-slate-950">
    <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
        <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-white">Plan the next 50 years in an afternoon.</h2>
        <p class="mt-6 text-lg text-slate-400 max-w-2xl mx-auto">Open the dashboard, add your first asset and watch your financial future take shape.</p>
        <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ url('/admin') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                Open dashboard
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
            <a href="{{ route('features') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                Browse features
            </a>
        </div>
    </div>
</section>
@endsection
