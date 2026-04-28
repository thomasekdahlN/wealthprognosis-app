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
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $page->get('hero.badge'), 'item' => route('about')],
                ],
            ],
            [
                '@type' => 'AboutPage',
                '@id' => route('about').'#about',
                'name' => $page->get('schema.name'),
                'description' => $page->get('schema.description'),
                'isPartOf' => ['@id' => route('home').'#website'],
                'about' => ['@id' => route('home').'#software'],
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

    <section class="relative hero-gradient overflow-hidden" aria-labelledby="about-title">
        <div class="absolute inset-0 grid-pattern opacity-40" aria-hidden="true"></div>
        <div class="relative max-w-4xl mx-auto px-6 lg:px-8 pt-12 pb-16 sm:pt-20 sm:pb-20 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                {{ $page->get('hero.badge') }}
            </span>
            <h1 id="about-title"
                class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                {!! $page->get('hero.title_html') !!}
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 leading-relaxed">
                {{ $page->get('hero.lead') }}
            </p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="mission-title">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 py-16 sm:py-20">
            <h2 id="mission-title" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                {{ $page->get('mission.heading') }}
            </h2>
            <div class="mt-6 space-y-5 text-slate-300 leading-relaxed">
                @foreach ((array) $page->get('mission.paragraphs', []) as $paragraph)
                    <p>{!! $paragraph !!}</p>
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="principles-title">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 sm:py-24">
            <h2 id="principles-title" class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                {{ $page->get('principles.heading') }}
            </h2>
            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ((array) $page->get('principles.items', []) as $principle)
                    <article class="rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                        <h3 class="text-lg font-semibold text-white">{{ $principle['title'] }}</h3>
                        <p class="mt-3 text-sm text-slate-300 leading-relaxed">{{ $principle['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950" aria-labelledby="about-cta-title">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-20 sm:py-28 text-center">
            <h2 id="about-cta-title" class="text-3xl sm:text-5xl font-bold tracking-tight text-white">
                {{ $page->get('closing.heading') }}
            </h2>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">{{ $page->get('closing.lead') }}</p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('pricing') }}"
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
