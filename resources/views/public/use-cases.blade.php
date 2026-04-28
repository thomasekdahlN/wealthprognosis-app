@extends('layouts.public')

@push('head')
    @php
        /** @var array<int, array<string, mixed>> $useCases */
        $useCases = (array) $page->get('cases', []);
    @endphp
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('public.breadcrumb.home'), 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $page->get('hero.badge'), 'item' => route('use-cases')],
                ],
            ],
            [
                '@type' => 'CollectionPage',
                '@id' => route('use-cases').'#use-cases',
                'name' => $page->get('schema.name'),
                'url' => route('use-cases'),
                'inLanguage' => app()->getLocale(),
                'isPartOf' => ['@id' => route('home').'#website'],
                'hasPart' => array_map(
                    static fn (array $c): array => [
                        '@type' => 'Article',
                        'headline' => (string) ($c['title'] ?? ''),
                        'about' => (string) ($c['badge'] ?? ''),
                        'description' => (string) ($c['problem'] ?? ''),
                        'url' => route('use-cases').'#'.(string) ($c['slug'] ?? ''),
                    ],
                    $useCases,
                ),
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

    <section class="relative hero-gradient overflow-hidden" aria-labelledby="use-cases-title">
        <div class="absolute inset-0 grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-5xl mx-auto px-6 lg:px-8 pt-12 pb-12 sm:pt-20 sm:pb-16 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                {{ $page->get('hero.badge') }}
            </span>
            <h1 id="use-cases-title"
                class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                {!! $page->get('hero.title_html') !!}
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                {{ $page->get('hero.lead') }}
            </p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-12">
            <nav aria-label="{{ $page->get('hero.badge') }}" class="flex flex-wrap gap-2">
                @foreach ($useCases as $case)
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
            @foreach ($useCases as $case)
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
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">
                                    {{ $page->get('labels.problem') }}</h3>
                                <p class="mt-3 text-sm text-slate-200 leading-relaxed">{{ $case['problem'] }}</p>
                            </div>
                            <div class="rounded-xl border border-white/5 bg-slate-950/40 p-5 md:col-span-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">
                                    {{ $page->get('labels.how') }}</h3>
                                <ul class="mt-3 space-y-2">
                                    @foreach ((array) ($case['how'] ?? []) as $step)
                                        <li class="flex gap-3 text-sm text-slate-200 leading-relaxed">
                                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-400"></span>
                                            <span>{{ $step }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="mt-6 rounded-xl border border-brand-400/15 bg-brand-500/5 p-5">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-brand-300">
                                {{ $page->get('labels.outcome') }}</h3>
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
            <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-white">
                {{ $page->get('closing.heading') }}</h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">{{ $page->get('closing.lead') }}</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/admin') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-400 text-slate-950 font-semibold px-6 py-3.5 text-base transition shadow-xl shadow-brand-500/30">
                    {{ $page->get('closing.cta_primary') }}
                </a>
                <a href="{{ route('faq') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    {{ $page->get('closing.cta_secondary') }}
                </a>
            </div>
        </div>
    </section>
@endsection
