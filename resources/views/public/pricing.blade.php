@extends('layouts.public')

@push('head')
    @php
        $allPlans = array_merge((array) $page->get('plans.private', []), (array) $page->get('plans.corporate', []));
        $offerCatalog = collect($allPlans)
            ->map(fn (array $plan): array => [
                '@type' => 'Offer',
                'name' => $plan['name'] ?? '',
                'description' => trim(html_entity_decode(strip_tags((string) ($plan['tagline'] ?? '')))),
                'price' => is_numeric(str_replace(' ', '', (string) ($plan['price'] ?? '')))
                    ? str_replace(' ', '', (string) $plan['price'])
                    : '0',
                'priceCurrency' => 'NOK',
                'availability' => 'https://schema.org/InStock',
                'url' => route('pricing').'#'.strtolower((string) ($plan['name'] ?? '')),
            ])->all();
    @endphp
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('public.breadcrumb.home'), 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $page->get('hero.badge'), 'item' => route('pricing')],
                ],
            ],
            [
                '@type' => 'Product',
                '@id' => route('pricing').'#product',
                'name' => 'Wealth Prognosis',
                'description' => $page->get('schema.product_description'),
                'brand' => ['@type' => 'Brand', 'name' => 'Wealth Prognosis'],
                'offers' => [
                    '@type' => 'AggregateOffer',
                    'priceCurrency' => 'NOK',
                    'lowPrice' => (string) $page->get('schema.low_price', '79'),
                    'highPrice' => (string) $page->get('schema.high_price', '1499'),
                    'offerCount' => count($offerCatalog),
                    'offers' => $offerCatalog,
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

    <section class="relative hero-gradient overflow-hidden" aria-labelledby="pricing-title">
        <div class="absolute inset-0 grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-6 lg:px-8 pt-12 pb-12 sm:pt-20 sm:pb-16 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                {{ $page->get('hero.badge') }}
            </span>
            <h1 id="pricing-title"
                class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                {!! $page->get('hero.title_html') !!}
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 leading-relaxed">
                {{ $page->get('hero.lead') }}
            </p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="plans-title">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 sm:py-20">
            <div class="max-w-2xl">
                <h2 id="plans-title" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                    {{ $page->get('plans.heading') }}</h2>
                <p class="mt-3 text-slate-300">{{ $page->get('plans.lead') }}</p>
            </div>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                @foreach ($allPlans as $plan)
                    @include('public.partials.pricing-card', ['plan' => $plan])
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="pricing-notes-title">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-16 sm:py-20">
            <h2 id="pricing-notes-title" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                {{ $page->get('notes.heading') }}</h2>
            <dl class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ((array) $page->get('notes.items', []) as $note)
                    <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                        <dt class="font-semibold text-white">{{ $note['q'] }}</dt>
                        <dd class="mt-2 text-sm text-slate-300 leading-relaxed">{{ $note['a'] }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-8 text-sm text-slate-400">{!! $page->get('notes.footer_html') !!}</p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="pricing-cta-title">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 id="pricing-cta-title" class="text-3xl sm:text-5xl font-bold tracking-tight text-white">
                {{ $page->get('closing.heading') }}</h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">{{ $page->get('closing.lead') }}</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/admin') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    {{ $page->get('closing.cta_primary') }}
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                        aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
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
