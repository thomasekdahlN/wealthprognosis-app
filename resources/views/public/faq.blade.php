@extends('layouts.public')

@php
    /** @var array<int, array<string, mixed>> $groups */
    $groups = (array) $page->get('groups', []);
@endphp

@push('head')
    @php
        $faqMainEntity = [];
        foreach ($groups as $group) {
            foreach ((array) ($group['items'] ?? []) as $item) {
                $faqMainEntity[] = [
                    '@type' => 'Question',
                    'name' => trim(html_entity_decode(strip_tags((string) ($item['q'] ?? '')))),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => trim(html_entity_decode(strip_tags((string) ($item['a'] ?? '')))),
                    ],
                ];
            }
        }
    @endphp
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'FAQPage',
                '@id' => route('faq').'#faq',
                'mainEntity' => $faqMainEntity,
                'inLanguage' => app()->getLocale(),
                'isPartOf' => ['@id' => route('home').'#website'],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('public.breadcrumb.home'), 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $page->get('hero.badge'), 'item' => route('faq')],
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

    <section class="relative hero-gradient overflow-hidden" aria-labelledby="faq-title">
        <div class="absolute inset-0 grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-5xl mx-auto px-6 lg:px-8 pt-12 pb-12 sm:pt-20 sm:pb-16 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                {{ $page->get('hero.badge') }}
            </span>
            <h1 id="faq-title"
                class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                {!! $page->get('hero.title_html') !!}
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                {{ $page->get('hero.lead') }}
            </p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-16 sm:py-24 space-y-16">
            @foreach ($groups as $group)
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">{!! $group['title'] !!}</h2>
                    <div class="mt-8 space-y-3">
                        @foreach ((array) ($group['items'] ?? []) as $item)
                            <details
                                class="group rounded-2xl border border-white/5 bg-white/[0.02] hover:border-white/10 transition open:border-brand-400/30 open:bg-white/[0.04]">
                                <summary class="flex items-start justify-between gap-4 cursor-pointer list-none p-5 sm:p-6">
                                    <h3 class="text-base sm:text-lg font-semibold text-white pr-2">{!! $item['q'] !!}
                                    </h3>
                                    <span
                                        class="shrink-0 mt-1 inline-flex items-center justify-center w-7 h-7 rounded-lg bg-white/5 text-slate-300 group-open:bg-brand-500/10 group-open:text-brand-300 transition"
                                        aria-hidden="true">
                                        <svg class="w-4 h-4 transition group-open:rotate-180" fill="none"
                                            viewBox="0 0 24 24" stroke-width="2.25" stroke="currentColor"
                                            aria-hidden="true" focusable="false">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </span>
                                </summary>
                                <div
                                    class="px-5 sm:px-6 pb-5 sm:pb-6 -mt-1 text-sm sm:text-base text-slate-300 leading-relaxed space-y-3">
                                    {!! $item['a'] !!}
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endforeach
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
                <a href="{{ route('pricing') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    {{ $page->get('closing.cta_secondary') }}
                </a>
            </div>
        </div>
    </section>
@endsection
