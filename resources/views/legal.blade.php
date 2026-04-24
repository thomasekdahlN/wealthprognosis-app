@extends('layouts.public')

@section('title', 'Legal — Terms, Privacy & Cookies')
@section('description',
    'Wealth Prognosis legal information: terms of service, privacy policy, data handling, cookies
    and your rights as a user. Plain-language and GDPR-aware.')
@section('og_type', 'article')

@php
    $lastUpdated = '2025-01-15';

    $sections = [
        [
            'slug' => 'terms',
            'title' => 'Terms of service',
            'intro' =>
                'These terms govern your use of Wealth Prognosis. By creating an account or using the service you agree to them.',
            'body' => [
                [
                    'h' => '1. What Wealth Prognosis is',
                    'p' =>
                        '<p>Wealth Prognosis is a financial planning and simulation system. It lets you record assets, liabilities, income and expenses, and projects your economy year by year under pessimistic, realistic and optimistic scenarios.</p><p class="mt-3">The service produces <strong class="text-white">estimates and projections</strong>. It is not financial, tax, legal or investment advice. You remain responsible for every financial decision you make based on its output.</p>',
                ],
                [
                    'h' => '2. Your account',
                    'p' =>
                        '<p>You must be at least 18 years old and provide accurate registration information. You are responsible for keeping your credentials secure and for every action performed under your account. Notify us immediately if you suspect unauthorised access.</p>',
                ],
                [
                    'h' => '3. Your data',
                    'p' =>
                        '<p>You own the data you enter. We store, process and project it so that the service can function for you. You can export everything to Excel or JSON at any time and delete your account on request, which triggers deletion of your data within 30 days except where retention is required by law.</p>',
                ],
                [
                    'h' => '4. Acceptable use',
                    'p' =>
                        '<p>You agree not to reverse-engineer the service, probe its security, upload malicious content, attempt to access other teams\' data, or use the service to break the law. Automated scraping and load testing are not permitted without written consent.</p>',
                ],
                [
                    'h' => '5. Subscriptions and payment',
                    'p' =>
                        '<p>Paid plans renew automatically at the end of each billing period until cancelled. You can cancel at any time; the cancellation takes effect at the end of the current period. Prices, tiers and free-trial terms are described on the pricing page and may change with 30 days\' notice for renewals.</p>',
                ],
                [
                    'h' => '6. Availability and changes',
                    'p' =>
                        '<p>We aim for high availability but do not guarantee uninterrupted service. We may update features, calculations or tax-rule configurations at any time. Breaking changes affecting your existing data will be announced in advance where practical.</p>',
                ],
                [
                    'h' => '7. Disclaimer of warranty',
                    'p' =>
                        '<p>The service is provided "as is". Tax rules, rates and thresholds are modelled in good faith but may differ from the current official rules at any point in time. Always verify important decisions with a qualified professional.</p>',
                ],
                [
                    'h' => '8. Limitation of liability',
                    'p' =>
                        '<p>To the fullest extent permitted by law, our total liability for any claim relating to the service is limited to the amount you have paid us in the 12 months preceding the claim. We are not liable for indirect or consequential losses, including lost profits, investment losses or tax penalties.</p>',
                ],
                [
                    'h' => '9. Governing law',
                    'p' =>
                        '<p>These terms are governed by Norwegian law. Disputes fall under the exclusive jurisdiction of the Norwegian courts, unless mandatory consumer-protection rules in your country of residence say otherwise.</p>',
                ],
            ],
        ],
        [
            'slug' => 'privacy',
            'title' => 'Privacy policy',
            'intro' => 'How we collect, use and protect your personal data. We keep it short, honest and GDPR-aware.',
            'body' => [
                [
                    'h' => 'What we collect',
                    'p' =>
                        '<ul class="list-disc pl-5 space-y-2"><li><strong class="text-white">Account data</strong> — name, email address, hashed password, team membership.</li><li><strong class="text-white">Financial data you enter</strong> — asset configurations, values, income, expenses, mortgages, simulation results, AI conversations.</li><li><strong class="text-white">Technical data</strong> — IP address, browser user-agent, timestamps, audit trail (created_by, updated_by, checksums).</li><li><strong class="text-white">Billing data</strong> — only for paid plans, handled by the payment provider; we store the subscription identifier and plan tier.</li></ul>',
                ],
                [
                    'h' => 'Why we process it',
                    'p' =>
                        '<p>To operate the service, run your simulations, support you, secure the system, and comply with legal obligations. We do not sell your data and we do not use it to train third-party models. The AI assistant only sees the data belonging to your team and uses it to answer your own questions.</p>',
                ],
                [
                    'h' => 'Legal basis (GDPR)',
                    'p' =>
                        '<p>Performance of the contract with you, our legitimate interest in running and improving the service, your consent where required (marketing emails, optional cookies), and legal obligation where applicable (accounting, tax).</p>',
                ],
                [
                    'h' => 'Where your data is stored',
                    'p' =>
                        '<p>Primary storage is in the European Union. Backups are encrypted at rest. We use sub-processors only where necessary (hosting, email, AI model providers) and list them on request.</p>',
                ],
                [
                    'h' => 'How long we keep it',
                    'p' =>
                        '<p>Active-account data is kept while your account exists. Backups roll off within 35 days. Invoices are retained for 5 years as required by Norwegian accounting law. Upon deletion of your account, financial configuration data is removed within 30 days.</p>',
                ],
                [
                    'h' => 'Your rights',
                    'p' =>
                        '<p>You can at any time request access, correction, export or deletion of your personal data, and withdraw consent where consent is the legal basis. You can also lodge a complaint with the Norwegian Data Protection Authority (<em>Datatilsynet</em>) or your local supervisory authority.</p>',
                ],
                [
                    'h' => 'Contact',
                    'p' =>
                        '<p>For any privacy matter contact us through the email address on our website. We respond to rights requests within 30 days.</p>',
                ],
            ],
        ],
        [
            'slug' => 'cookies',
            'title' => 'Cookies',
            'intro' => 'What cookies we set and why — the short version.',
            'body' => [
                [
                    'h' => 'Strictly necessary',
                    'p' =>
                        '<p>We set a small number of first-party cookies required for the service to work — for example a session cookie that keeps you signed in, and a CSRF token that protects forms from cross-site attacks. These cannot be disabled without breaking the app.</p>',
                ],
                [
                    'h' => 'Preferences',
                    'p' =>
                        '<p>If you change locale or theme, we may store your choice in a cookie so the site remembers it on your next visit. These cookies do not track you across sites.</p>',
                ],
                [
                    'h' => 'Analytics',
                    'p' =>
                        '<p>We currently do not run any third-party analytics cookies on the marketing site. If that changes in the future we will update this page and, where required, ask for your consent first.</p>',
                ],
                [
                    'h' => 'Controlling cookies',
                    'p' =>
                        '<p>You can clear or block cookies in your browser settings. Blocking the session cookie will sign you out of the dashboard.</p>',
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
                Legal
            </span>
            <h1 class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                Terms, privacy<br>and your rights.
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                The rules we operate under, how we look after your data, and how you can control both. Plain language —
                no surprises.
            </p>
            <p class="mt-6 text-xs uppercase tracking-wider text-slate-400">Last updated {{ $lastUpdated }}</p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-12">
            <nav aria-label="Legal sections" class="flex flex-wrap gap-2">
                @foreach ($sections as $section)
                    <a href="#{{ $section['slug'] }}"
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.03] hover:border-brand-400/40 hover:bg-brand-500/10 px-4 py-2 text-sm text-slate-200 hover:text-white transition">
                        {!! $section['title'] !!}
                    </a>
                @endforeach
            </nav>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 pb-20 sm:pb-28 space-y-20">
            @foreach ($sections as $section)
                <article id="{{ $section['slug'] }}" class="scroll-mt-24">
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">{!! $section['title'] !!}</h2>
                    <p class="mt-3 text-base text-slate-300">{{ $section['intro'] }}</p>
                    <div class="mt-8 space-y-6">
                        @foreach ($section['body'] as $block)
                            <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-6 sm:p-7">
                                <h3 class="text-lg font-semibold text-white">{!! $block['h'] !!}</h3>
                                <div class="mt-3 text-sm sm:text-base text-slate-300 leading-relaxed">
                                    {!! $block['p'] !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-white">Questions?</h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">The FAQ covers the most common ones, including data
                residency, exports and multi-tenancy.</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('faq') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    Read the FAQ
                </a>
                <a href="{{ route('pricing') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    See pricing
                </a>
            </div>
        </div>
    </section>
@endsection

@push('head')
    @php
        $legalSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebPage',
                    '@id' => url()->current() . '#legal',
                    'name' => 'Legal — Terms, Privacy and Cookies',
                    'url' => url()->current(),
                    'inLanguage' => app()->getLocale(),
                    'isPartOf' => ['@id' => url('/') . '#website'],
                    'dateModified' => $lastUpdated,
                    'about' => array_map(
                        fn(array $s): array => [
                            '@type' => 'Thing',
                            'name' => trim(html_entity_decode(strip_tags($s['title']))),
                        ],
                        $sections,
                    ),
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Legal', 'item' => route('legal')],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($legalSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
