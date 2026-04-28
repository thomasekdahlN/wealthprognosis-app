@extends('layouts.public')

@push('head')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'SoftwareApplication',
                '@id' => url('/').'#software',
                'name' => 'Wealth Prognosis',
                'applicationCategory' => 'FinanceApplication',
                'operatingSystem' => 'Web',
                'description' => $page->description,
                'url' => url('/'),
                'softwareVersion' => '1.0',
                'inLanguage' => ['en', 'no'],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '79',
                    'priceCurrency' => 'NOK',
                    'url' => route('pricing'),
                ],
                'featureList' => $page->get('schema.feature_list', []),
                'publisher' => ['@id' => url('/').'#organization'],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => __('public.breadcrumb.home'),
                        'item' => route('home'),
                    ],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <section id="overview" class="relative hero-gradient overflow-hidden" aria-labelledby="hero-title">
        <div class="absolute inset-0 grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 pt-20 pb-24 sm:pt-28 sm:pb-32">
            <div class="max-w-3xl">
                <h1 id="hero-title" class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                    {!! $page->get('hero.title_html') !!}
                </h1>
                <p class="mt-6 text-lg sm:text-xl text-slate-300 leading-relaxed">{{ $page->get('hero.lead') }}</p>
                <div class="mt-10 flex flex-col sm:flex-row gap-4">
                    <a href="{{ url('/admin') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                        {{ $page->get('hero.cta_primary') }}
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            aria-hidden="true" focusable="false">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <a href="{{ route('features') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                        {{ $page->get('hero.cta_secondary') }}
                    </a>
                </div>
            </div>

            <dl class="mt-20 grid grid-cols-2 lg:grid-cols-4 gap-6 max-w-4xl"
                aria-label="{{ $page->get('hero.stats_label') }}">
                @foreach ((array) $page->get('hero.stats', []) as $stat)
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
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">{{ $page->get('features.heading') }}
                </h2>
                <p class="mt-4 text-lg text-slate-300">{{ $page->get('features.lead') }}</p>
            </div>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ((array) $page->get('features.items', []) as $feature)
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
                    {{ $page->get('features.cta') }}
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
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">
                    {{ $page->get('how_it_works.heading') }}</h2>
                <p class="mt-4 text-lg text-slate-300">{{ $page->get('how_it_works.lead') }}</p>
            </div>

            <ol class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ((array) $page->get('how_it_works.steps', []) as $step)
                    <li class="relative rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                        <div class="text-xs font-mono text-brand-300 tracking-widest" aria-hidden="true">
                            {{ $step['step'] }}</div>
                        <h3 class="mt-3 text-lg font-semibold text-white"><span class="sr-only">{{ __('public.step') }}
                                {{ (int) $step['step'] }}:</span> {{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm text-slate-300 leading-relaxed">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    @include('public.partials.home-simulation', ['page' => $page])

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-white">{{ $page->get('closing.heading') }}</h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">{{ $page->get('closing.lead') }}</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/admin') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    {{ $page->get('closing.cta_primary') }}
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                        aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                <a href="{{ route('features') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    {{ $page->get('closing.cta_secondary') }}
                </a>
            </div>
        </div>
    </section>
@endsection
