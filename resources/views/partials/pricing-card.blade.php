@php
    /** @var array<string, mixed> $plan */
    $isFeatured = $plan['featured'] ?? false;
    $isExternal = $plan['ctaExternal'] ?? false;
    $anchorId = strtolower($plan['name']);
    $isNumeric = is_numeric(str_replace(' ', '', (string) $plan['price']));
@endphp

<article id="{{ $anchorId }}"
    @class([
        'relative rounded-2xl p-6 flex flex-col',
        'border border-brand-400/40 bg-gradient-to-b from-brand-500/10 to-transparent shadow-xl shadow-brand-500/10' => $isFeatured,
        'border border-white/5 bg-white/[0.02]' => ! $isFeatured,
    ])
    aria-labelledby="plan-{{ $anchorId }}-name">
    @if ($isFeatured)
        <span
            class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center gap-1 rounded-full bg-brand-500 text-slate-950 text-xs font-semibold px-3 py-1">
            Most popular
        </span>
    @endif

    <header>
        <h3 id="plan-{{ $anchorId }}-name" class="text-lg font-semibold text-white">{{ $plan['name'] }}</h3>
        <p class="mt-1 text-sm text-slate-300">{{ $plan['tagline'] }}</p>
    </header>

    <div class="mt-6 flex items-baseline gap-2">
        @if ($isNumeric)
            <span class="text-xs text-slate-400" aria-hidden="true">NOK</span>
            <span class="text-4xl font-extrabold tracking-tight text-white">{{ $plan['price'] }}</span>
            <span class="sr-only">Norwegian kroner</span>
        @else
            <span class="text-4xl font-extrabold tracking-tight text-white">{{ $plan['price'] }}</span>
        @endif
        @if (! empty($plan['period']))
            <span class="text-sm text-slate-400">{{ $plan['period'] }}</span>
        @endif
    </div>

    <ul class="mt-6 space-y-3 text-sm text-slate-300 flex-1">
        @foreach ($plan['features'] as $feature)
            <li class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-brand-300" fill="none" viewBox="0 0 24 24"
                    stroke-width="2.5" stroke="currentColor" aria-hidden="true" focusable="false">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span>{!! $feature !!}</span>
            </li>
        @endforeach
    </ul>

    <div class="mt-8">
        <a href="{{ $plan['ctaUrl'] }}"
            @if ($isExternal) target="_blank" rel="noopener noreferrer" @endif
            @class([
                'inline-flex items-center justify-center gap-2 w-full rounded-xl font-semibold px-4 py-3 text-sm transition',
                'bg-brand-500 hover:bg-brand-400 text-slate-950 shadow-lg shadow-brand-500/20' => $isFeatured,
                'border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 text-white' => ! $isFeatured,
            ])>
            {{ $plan['cta'] }}
            @if ($isExternal)
                <span class="sr-only"> (opens in a new tab)</span>
            @endif
        </a>
    </div>
</article>
