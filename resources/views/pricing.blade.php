@extends('layouts.public')

@section('title', 'Pricing — Wealth Prognosis')
@section('description',
    'Simple, transparent pricing for Wealth Prognosis — free self-hosted, hosted plans for private users
    and families, and dedicated tiers for advisors, businesses and enterprises.')

    @php
        $privatePlans = [
            [
                'name' => 'Self-hosted',
                'tagline' => 'For developers and tinkerers',
                'price' => '0',
                'period' => 'forever',
                'cta' => 'Open on GitHub',
                'ctaUrl' => 'https://github.com/thomasek/wealthprognosis-app',
                'ctaExternal' => true,
                'featured' => false,
                'features' => [
                    'Full MIT-licensed source',
                    'Run on your own infrastructure',
                    'All features included',
                    'Community support on GitHub',
                ],
            ],
            [
                'name' => 'Solo',
                'tagline' => 'For one person planning one life',
                'price' => '79',
                'period' => '/ month',
                'cta' => 'Start 30-day trial',
                'ctaUrl' => '/admin',
                'featured' => true,
                'features' => [
                    'Hosted on secure EU infrastructure',
                    '1 user, unlimited assets',
                    'AI assistant included',
                    'Excel &amp; PDF export',
                    'Automatic backups',
                    'Email support',
                ],
            ],
            [
                'name' => 'Family',
                'tagline' => 'For households planning together',
                'price' => '149',
                'period' => '/ month',
                'cta' => 'Start 30-day trial',
                'ctaUrl' => '/admin',
                'featured' => false,
                'features' => [
                    'Everything in Solo',
                    'Up to 5 household members',
                    'Shared household prognosis',
                    'Per-member private assets',
                    'Priority email support',
                ],
            ],
        ];

        $corporatePlans = [
            [
                'name' => 'Advisor',
                'tagline' => 'For financial advisors',
                'price' => '499',
                'period' => '/ advisor / month',
                'cta' => 'Contact sales',
                'ctaUrl' => 'mailto:sales@wealthprognosis.app?subject=Advisor%20plan',
                'featured' => false,
                'features' => [
                    'Manage up to 25 client accounts',
                    'Per-client team isolation',
                    'White-label dashboard',
                    'Signed PDF reports',
                    'Client import &amp; export',
                    'Business-hours support',
                ],
            ],
            [
                'name' => 'Business',
                'tagline' => 'For companies and advisory firms',
                'price' => '1 499',
                'period' => '/ month',
                'cta' => 'Contact sales',
                'ctaUrl' => 'mailto:sales@wealthprognosis.app?subject=Business%20plan',
                'featured' => true,
                'features' => [
                    'Up to 10 internal users',
                    'Up to 100 client accounts',
                    'SSO &amp; role-based access',
                    'Audit log export',
                    'Custom tax configurations',
                    'Dedicated onboarding',
                ],
            ],
            [
                'name' => 'Enterprise',
                'tagline' => 'For banks, funds and large firms',
                'price' => 'Custom',
                'period' => '',
                'cta' => 'Talk to us',
                'ctaUrl' => 'mailto:sales@wealthprognosis.app?subject=Enterprise',
                'featured' => false,
                'features' => [
                    'Unlimited users &amp; clients',
                    'On-premise or dedicated cloud',
                    'SLA &amp; 24/7 support',
                    'Custom integrations',
                    'Security &amp; compliance reviews',
                    'Named account manager',
                ],
            ],
        ];
    @endphp

    @push('head')
        @php
            $offerCatalog = [];
            foreach (array_merge($privatePlans, $corporatePlans) as $plan) {
                $offerCatalog[] = [
                    '@type' => 'Offer',
                    'name' => $plan['name'],
                    'description' => trim(html_entity_decode(strip_tags($plan['tagline']))),
                    'price' => is_numeric(str_replace(' ', '', $plan['price']))
                        ? str_replace(' ', '', $plan['price'])
                        : '0',
                    'priceCurrency' => 'NOK',
                    'availability' => 'https://schema.org/InStock',
                    'url' => route('pricing') . '#' . strtolower($plan['name']),
                ];
            }
            $schema = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Pricing', 'item' => route('pricing')],
                        ],
                    ],
                    [
                        '@type' => 'Product',
                        '@id' => route('pricing') . '#product',
                        'name' => 'Wealth Prognosis',
                        'description' => 'Financial planning and simulation SaaS with accurate Norwegian taxation.',
                        'brand' => ['@type' => 'Brand', 'name' => 'Wealth Prognosis'],
                        'offers' => [
                            '@type' => 'AggregateOffer',
                            'priceCurrency' => 'NOK',
                            'lowPrice' => '0',
                            'highPrice' => '1499',
                            'offerCount' => count($offerCatalog),
                            'offers' => $offerCatalog,
                        ],
                    ],
                ],
            ];
        @endphp
        <script type="application/ld+json">
    {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endpush

@section('content')
    <nav aria-label="Breadcrumb" class="max-w-7xl mx-auto px-6 lg:px-8 pt-6 text-sm text-slate-400">
        <ol class="flex items-center gap-2">
            <li><a href="{{ url('/') }}" class="hover:text-white transition">Home</a></li>
            <li aria-hidden="true" class="text-slate-600">/</li>
            <li aria-current="page" class="text-slate-200">Pricing</li>
        </ol>
    </nav>

    <section class="relative hero-gradient overflow-hidden" aria-labelledby="pricing-title">
        <div class="absolute inset-0 grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-6 lg:px-8 pt-12 pb-12 sm:pt-20 sm:pb-16 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                Pricing
            </span>
            <h1 id="pricing-title"
                class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                Simple pricing. <span
                    class="bg-gradient-to-r from-brand-300 via-brand-400 to-emerald-200 bg-clip-text text-transparent">Long-term
                    thinking.</span>
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 leading-relaxed">
                Self-host for free, or pick a hosted plan that fits your life or your business. All prices in NOK,
                excluding VAT. 30-day trial on hosted plans, cancel any time.
            </p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="private-title">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 sm:py-20">
            <div class="max-w-2xl">
                <h2 id="private-title" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">For private
                    users</h2>
                <p class="mt-3 text-slate-300">Households planning for financial independence, early retirement or just
                    clear long-term finances.</p>
            </div>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($privatePlans as $plan)
                    @include('partials.pricing-card', ['plan' => $plan])
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-gradient-to-b from-slate-950 to-slate-900/50"
        aria-labelledby="corporate-title">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 sm:py-20">
            <div class="max-w-2xl">
                <h2 id="corporate-title" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">For
                    corporate users</h2>
                <p class="mt-3 text-slate-300">Financial advisors, advisory firms, banks and funds that need to plan
                    with and for their clients.</p>
            </div>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($corporatePlans as $plan)
                    @include('partials.pricing-card', ['plan' => $plan])
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="pricing-notes-title">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-16 sm:py-20">
            <h2 id="pricing-notes-title" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">Good to know
            </h2>
            <dl class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ([['q' => 'Can I self-host and still get support?', 'a' => 'Yes. A paid support add-on is available for self-hosted deployments — contact sales for pricing. Community support on GitHub is always free.'], ['q' => 'Do you offer annual billing?', 'a' => 'Annual plans are billed with a 2-month discount on all hosted tiers. Contact sales to switch.'], ['q' => 'Is the Norwegian tax configuration included?', 'a' => 'Yes. Every hosted plan ships with the maintained Norwegian tax configuration. Custom jurisdictions are available on Business and Enterprise.'], ['q' => 'What about data ownership?', 'a' => 'Your data is yours. Full export to JSON and Excel on every plan. Hosted plans run on EU infrastructure with encrypted backups.']] as $note)
                    <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                        <dt class="font-semibold text-white">{{ $note['q'] }}</dt>
                        <dd class="mt-2 text-sm text-slate-300 leading-relaxed">{{ $note['a'] }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-8 text-sm text-slate-400">More questions? Read the <a href="{{ route('faq') }}"
                    class="text-brand-300 hover:text-brand-200 underline underline-offset-2">FAQ</a> or <a
                    href="mailto:sales@wealthprognosis.app"
                    class="text-brand-300 hover:text-brand-200 underline underline-offset-2">email
                    sales&#64;wealthprognosis.app</a>.</p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="pricing-cta-title">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 id="pricing-cta-title" class="text-3xl sm:text-5xl font-bold tracking-tight text-white">Start planning
                today.</h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">30-day trial on every hosted plan. No credit card
                required.</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/admin') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    Start trial
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
