@extends('layouts.public')

@php
    /** @var array<int, array<string, mixed>> $widgets */
    $widgets = (array) $page->get('widgets', []);
    /** @var array<int, array<string, mixed>> $taxFormulas */
    $taxFormulas = (array) $page->get('tax_formulas', []);
    /** @var array<int, array<string, mixed>> $prognosis */
    $prognosis = (array) $page->get('prognosis', []);
    /** @var array<int, array<string, mixed>> $pillars */
    $pillars = (array) $page->get('pillars', []);
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
        .katex { font-size: 1.05em; color: #e2e8f0; }
        .katex-display { margin: 0; padding: 1rem 0; overflow-x: auto; overflow-y: hidden; }
        .katex-display>.katex { color: #f1f5f9; }
    </style>
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'TechArticle',
                '@id' => route('methodology').'#article',
                'headline' => $page->get('schema.headline'),
                'description' => $page->get('schema.description'),
                'inLanguage' => app()->getLocale(),
                'isPartOf' => ['@id' => route('home').'#website'],
                'about' => (array) $page->get('schema.about', []),
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('public.breadcrumb.home'), 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $page->get('hero.badge'), 'item' => route('methodology')],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <nav aria-label="{{ __('public.breadcrumb.aria') }}"
        class="max-w-7xl mx-auto px-6 lg:px-8 pt-6 text-sm text-slate-400">
        <ol class="flex items-center gap-2">
            <li><a href="{{ route('home') }}"
                    class="hover:text-white transition">{{ __('public.breadcrumb.home') }}</a></li>
            <li aria-hidden="true" class="text-slate-600">/</li>
            <li aria-current="page" class="text-slate-200">{{ $page->get('hero.badge') }}</li>
        </ol>
    </nav>

    <section class="relative hero-gradient overflow-hidden" aria-labelledby="methodology-title">
        <div class="absolute inset-0 grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-5xl mx-auto px-6 lg:px-8 pt-12 pb-12 sm:pt-20 sm:pb-16 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                {{ $page->get('hero.badge') }}
            </span>
            <h1 id="methodology-title"
                class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                {!! $page->get('hero.title_html') !!}
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                {{ $page->get('hero.lead') }}
            </p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#widgets"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    {{ $page->get('hero.cta_primary') }}
                </a>
                <a href="{{ route('glossary') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    {{ $page->get('hero.cta_secondary') }}
                </a>
            </div>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-16 grid md:grid-cols-3 gap-6">
            @foreach ($pillars as $pillar)
                <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                    <h3 class="text-sm font-semibold text-brand-300">{{ $pillar['title'] }}</h3>
                    <p class="mt-2 text-sm text-slate-300 leading-relaxed">{{ $pillar['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section id="widgets" class="relative border-t border-white/5 bg-slate-950 scroll-mt-24">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20">
            <div class="max-w-2xl">
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">
                    {{ $page->get('widgets_section.heading') }}</h2>
                <p class="mt-4 text-base text-slate-300 leading-relaxed">
                    {{ $page->get('widgets_section.lead') }}</p>
            </div>

            <nav aria-label="{{ $page->get('widgets_section.heading') }}" class="mt-10 flex flex-wrap gap-2">
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
                                <h4 class="text-xs font-semibold text-brand-300 uppercase tracking-wider">
                                    {{ $page->get('labels.inputs') }}</h4>
                                <ul class="mt-2 space-y-1 text-sm text-slate-300">
                                    @foreach ((array) ($w['inputs'] ?? []) as $input)
                                        <li class="flex gap-2">
                                            <span class="text-brand-400">•</span>
                                            <code class="text-slate-200">{!! $input !!}</code>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div>
                                <h4 class="text-xs font-semibold text-brand-300 uppercase tracking-wider">
                                    {{ $page->get('labels.caveats') }}</h4>
                                <p class="mt-2 text-sm text-slate-300 leading-relaxed">{!! $w['caveats'] !!}</p>
                            </div>
                        </div>

                        <div class="mt-8 rounded-2xl border border-brand-400/20 bg-slate-900/60 p-6 sm:p-8">
                            <h4 class="text-xs font-semibold text-brand-300 uppercase tracking-wider">
                                {{ $page->get('labels.formula') }}</h4>
                            <div class="mt-3 text-slate-100">{!! $w['formula'] !!}</div>
                            <p class="mt-4 text-xs text-slate-400 leading-relaxed">{!! $w['legend'] !!}</p>
                        </div>

                        <div class="mt-6 rounded-2xl border border-white/5 bg-slate-900/30 p-6">
                            <h4 class="text-xs font-semibold text-brand-300 uppercase tracking-wider">
                                {{ $page->get('labels.example') }}</h4>
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
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">
                    {{ $page->get('taxation_section.heading') }}</h2>
                <p class="mt-4 text-base text-slate-300 leading-relaxed">
                    {{ $page->get('taxation_section.lead') }}</p>
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
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">
                    {{ $page->get('prognosis_section.heading') }}</h2>
                <p class="mt-4 text-base text-slate-300 leading-relaxed">
                    {{ $page->get('prognosis_section.lead') }}</p>
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
            <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-white">
                {{ $page->get('closing.heading') }}</h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">{{ $page->get('closing.lead') }}</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/admin') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    {{ $page->get('closing.cta_primary') }}
                </a>
                <a href="{{ route('features') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    {{ $page->get('closing.cta_secondary') }}
                </a>
            </div>
        </div>
    </section>
@endsection
