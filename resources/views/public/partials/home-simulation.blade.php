{{-- @var \App\Support\ValueObjects\MarkdownPage $page --}}
<section id="simulation" class="relative border-t border-white/5 bg-slate-950" aria-labelledby="simulation-title">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 sm:py-28">
        <div class="max-w-2xl">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                {{ $page->get('simulation.badge') }}
            </span>
            <h2 id="simulation-title" class="mt-5 text-3xl sm:text-4xl font-bold tracking-tight text-white">
                {{ $page->get('simulation.heading') }}</h2>
            <p class="mt-4 text-lg text-slate-300 leading-relaxed">{{ $page->get('simulation.lead') }}</p>
        </div>

        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ((array) $page->get('simulation.scenarios', []) as $scenario)
                <article class="rounded-2xl border border-white/5 bg-white/[0.02] p-6">
                    <span
                        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-brand-500/10 text-brand-300 ring-1 ring-brand-400/20 text-xl font-bold"
                        aria-hidden="true">{{ $scenario['badge'] }}</span>
                    <h3 class="mt-4 text-lg font-semibold text-white">{{ $scenario['name'] }}</h3>
                    <p class="mt-2 text-sm text-slate-300 leading-relaxed">{{ $scenario['body'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-16 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-8">
                <h3 class="text-xl font-semibold text-white">{{ $page->get('simulation.coverage_heading') }}</h3>
                <ul class="mt-6 space-y-3 text-sm text-slate-300">
                    @foreach ((array) $page->get('simulation.coverage', []) as $item)
                        <li class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-brand-300" fill="none" viewBox="0 0 24 24"
                                stroke-width="2.5" stroke="currentColor" aria-hidden="true" focusable="false">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-8">
                <h3 class="text-xl font-semibold text-white">{{ $page->get('simulation.events_heading') }}</h3>
                <ul class="mt-6 space-y-3 text-sm text-slate-300">
                    @foreach ((array) $page->get('simulation.events', []) as $item)
                        <li class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-brand-300" fill="none" viewBox="0 0 24 24"
                                stroke-width="2.5" stroke="currentColor" aria-hidden="true" focusable="false">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>
