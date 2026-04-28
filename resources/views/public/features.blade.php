@extends('layouts.public')

@push('head')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('public.breadcrumb.home'), 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $page->title, 'item' => route('features')],
                ],
            ],
            [
                '@type' => 'ItemList',
                '@id' => route('features').'#features',
                'name' => $page->title,
                'itemListElement' => collect((array) $page->get('schema.feature_list', []))
                    ->values()
                    ->map(fn (array $item, int $i): array => [
                        '@type' => 'ListItem',
                        'position' => $i + 1,
                        'name' => $item['name'] ?? '',
                        'description' => $item['description'] ?? '',
                    ])->all(),
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

    <section class="relative hero-gradient overflow-hidden" aria-labelledby="features-title">
        <div class="absolute inset-0 grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-5xl mx-auto px-6 lg:px-8 pt-12 pb-16 sm:pt-20 sm:pb-20 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                {{ $page->get('hero.badge') }}
            </span>
            <h1 id="features-title"
                class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                {!! $page->get('hero.title_html') !!}
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                {{ $page->get('hero.lead') }}
            </p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-label="{{ $page->get('groups_label') }}">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 sm:py-24 space-y-20">
            @foreach ((array) $page->get('groups', []) as $index => $group)
                @php($groupId = 'feature-group-'.$index)
                <article class="grid grid-cols-1 lg:grid-cols-3 gap-10" aria-labelledby="{{ $groupId }}">
                    <div class="lg:col-span-1">
                        <h2 id="{{ $groupId }}" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                            {!! $group['title'] !!}</h2>
                        <p class="mt-4 text-slate-300 leading-relaxed">{{ $group['intro'] }}</p>
                    </div>
                    <dl class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        @foreach ((array) ($group['items'] ?? []) as $item)
                            <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-5">
                                <dt class="flex items-center gap-2 font-semibold text-white">
                                    <svg class="w-4 h-4 text-brand-300 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="currentColor" aria-hidden="true" focusable="false">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    <span>{!! $item['name'] !!}</span>
                                </dt>
                                <dd class="mt-2 text-sm text-slate-300 leading-relaxed">{!! $item['body'] !!}</dd>
                            </div>
                        @endforeach
                    </dl>
                </article>
            @endforeach
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="features-cta-title">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 id="features-cta-title" class="text-3xl sm:text-5xl font-bold tracking-tight text-white">
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
                <a href="{{ route('home') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white font-semibold px-6 py-3.5 text-base transition">
                    {{ $page->get('closing.cta_secondary') }}
                </a>
            </div>
        </div>
    </section>
@endsection
