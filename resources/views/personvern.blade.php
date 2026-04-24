@extends('layouts.public')

@section('title', 'Personvernerklæring — Wealth Prognosis')
@section('description',
    'Personvernerklæring for Wealth Prognosis levert av Ekdahl Enterprises AS. Hvilke personopplysninger vi
    behandler, hvorfor, rettslig grunnlag (GDPR), lagringstid og dine rettigheter.')
@section('og_type', 'article')

@php
    $lastUpdated = '2026-04-24';

    $sections = [
        [
            'slug' => 'behandlingsansvarlig',
            'title' => 'Behandlingsansvarlig',
            'body' =>
                '<p><strong class="text-white">Ekdahl Enterprises AS</strong> (Org.nr 933 662 541) er behandlingsansvarlig for personopplysningene som behandles i forbindelse med tjenesten Wealth Prognosis.</p>
<p class="mt-3">Postadresse: Smørbukkveien 3, 3123 Tønsberg, Norge.<br>
Kontakt: <a href="mailto:thomas@ekdahl.no" class="text-brand-300 hover:text-brand-200">thomas@ekdahl.no</a> · <a href="tel:+4791143630" class="text-brand-300 hover:text-brand-200">+47 911 43 630</a>.</p>',
        ],
        [
            'slug' => 'hva-vi-samler-inn',
            'title' => 'Hvilke opplysninger vi behandler',
            'body' =>
                '<ul class="list-disc pl-5 space-y-2">
<li><strong class="text-white">Kontoopplysninger</strong> — navn, e‑postadresse, passord (lagret som hash), teamtilknytning, språk‑ og visningspreferanser.</li>
<li><strong class="text-white">Finansielle data du selv legger inn</strong> — formueskonfigurasjoner, eiendeler, verdier, inntekter, utgifter, lån, skatte‑ og pensjonsparametre, simuleringsscenarier, resultater og AI‑samtaler.</li>
<li><strong class="text-white">Tekniske opplysninger</strong> — IP‑adresse, nettleser‑user‑agent, tidsstempler og revisjonsspor (created_by, updated_by, SHA‑256 sjekksummer) brukt for sporbarhet og sikkerhet.</li>
<li><strong class="text-white">Fakturerings­opplysninger</strong> — kun for betalte abonnement; selve betalingen håndteres av betalings­leverandøren, og vi lagrer kun abonnements‑ID og valgt plan.</li>
<li><strong class="text-white">E‑post og supporthenvendelser</strong> — innhold i meldinger du sender oss behandles for å svare og forbedre tjenesten.</li>
</ul>',
        ],
        [
            'slug' => 'formal',
            'title' => 'Formålet med behandlingen',
            'body' =>
                '<p>Vi behandler personopplysninger for å:</p>
<ul class="list-disc pl-5 mt-3 space-y-2">
<li>levere tjenesten, kjøre simuleringene dine og vise resultater;</li>
<li>autentisere deg og sikre kontoen din mot uautorisert tilgang;</li>
<li>gi kundestøtte og svare på henvendelser;</li>
<li>drifte, overvåke, feilsøke og forbedre tjenesten;</li>
<li>oppfylle lovpålagte krav til bokføring og skatt;</li>
<li>sende viktig informasjon om kontoen (f.eks. sikkerhets‑ eller tjeneste­varsler).</li>
</ul>
<p class="mt-3">Vi <strong class="text-white">selger ikke</strong> personopplysninger, og vi bruker ikke dine data til å trene tredje­parts AI‑modeller. AI‑assistenten ser kun data som tilhører ditt team, og svarer kun på dine egne spørsmål.</p>',
        ],
        [
            'slug' => 'rettslig-grunnlag',
            'title' => 'Rettslig grunnlag (GDPR art. 6)',
            'body' =>
                '<ul class="list-disc pl-5 space-y-2">
<li><strong class="text-white">Avtale</strong> (art. 6 nr. 1 bokstav b) — for å oppfylle bruker­avtalen og levere tjenesten du har registrert deg for.</li>
<li><strong class="text-white">Berettiget interesse</strong> (art. 6 nr. 1 bokstav f) — for sikkerhet, misbruks­forebygging, teknisk drift og produkt­forbedring. Vi veier alltid denne interessen mot dine rettigheter.</li>
<li><strong class="text-white">Samtykke</strong> (art. 6 nr. 1 bokstav a) — der samtykke er nødvendig, f.eks. for ikke‑nødvendige informasjons­kapsler eller markedsførings­e‑post. Samtykke kan når som helst trekkes tilbake.</li>
<li><strong class="text-white">Rettslig forpliktelse</strong> (art. 6 nr. 1 bokstav c) — for å oppfylle bokførings‑ og skatte­plikter etter norsk lov.</li>
</ul>',
        ],
        [
            'slug' => 'lagring',
            'title' => 'Hvor dataene lagres',
            'body' =>
                '<p>Primær lagring skjer innenfor EU/EØS. Data i hvile er kryptert, og all nett­trafikk mellom deg og tjenesten er kryptert med TLS.</p>
<p class="mt-3">Vi bruker databehandlere (under­leverandører) der det er nødvendig — typisk for drifts­hosting, e‑post­utsendelse og AI‑modell­leverandører. En fullstendig liste over aktive databehandlere gis på forespørsel til <a href="mailto:thomas@ekdahl.no" class="text-brand-300 hover:text-brand-200">thomas@ekdahl.no</a>. Overføringer utenfor EØS skjer kun med gyldig overførings­grunnlag (standard personvern­bestemmelser eller tilsvarende).</p>',
        ],
        [
            'slug' => 'lagringstid',
            'title' => 'Hvor lenge vi lagrer opplysningene',
            'body' =>
                '<ul class="list-disc pl-5 space-y-2">
<li><strong class="text-white">Aktiv konto</strong> — så lenge du har konto hos oss.</li>
<li><strong class="text-white">Sikkerhets­kopier</strong> — roteres ut innen 35 dager.</li>
<li><strong class="text-white">Fakturaer og bokførings­pliktig materiale</strong> — 5 år i tråd med bokføringsloven.</li>
<li><strong class="text-white">Ved sletting av konto</strong> — finansielle konfigurasjons­data slettes innen 30 dager, med unntak av data vi er lovpålagt å oppbevare.</li>
<li><strong class="text-white">Revisjons­logg</strong> — lagres så lenge det er saklig behov for sporbarhet, normalt inntil 12 måneder etter siste aktivitet.</li>
</ul>',
        ],
        [
            'slug' => 'deling',
            'title' => 'Deling med andre',
            'body' =>
                '<p>Vi deler ikke personopplysninger med tredjepart utover:</p>
<ul class="list-disc pl-5 mt-3 space-y-2">
<li>databehandlere som utfører tjenester på våre vegne under en databehandler­avtale;</li>
<li>offentlige myndigheter dersom vi er rettslig forpliktet til det;</li>
<li>nye eiere dersom virksomheten helt eller delvis overdras — du vil i så fall bli varslet på forhånd.</li>
</ul>',
        ],
        [
            'slug' => 'rettigheter',
            'title' => 'Dine rettigheter',
            'body' =>
                '<p>Etter personvernforordningen har du rett til å:</p>
<ul class="list-disc pl-5 mt-3 space-y-2">
<li>få innsyn i hvilke opplysninger vi har registrert om deg;</li>
<li>få uriktige opplysninger rettet;</li>
<li>få opplysninger slettet (retten til å bli glemt);</li>
<li>kreve begrensning eller protestere mot behandlingen;</li>
<li>få utlevert dine data i et maskinlesbart format (data­portabilitet);</li>
<li>trekke tilbake samtykke der behandlingen er basert på samtykke.</li>
</ul>
<p class="mt-3">Henvendelser sendes til <a href="mailto:thomas@ekdahl.no" class="text-brand-300 hover:text-brand-200">thomas@ekdahl.no</a>. Vi svarer normalt innen 30 dager. Du har også rett til å klage til <a href="https://www.datatilsynet.no" target="_blank" rel="noopener" class="text-brand-300 hover:text-brand-200">Datatilsynet</a>.</p>',
        ],
        [
            'slug' => 'informasjonskapsler',
            'title' => 'Informasjonskapsler (cookies)',
            'body' =>
                '<p>Vi benytter kun første­parts informasjons­kapsler som er nødvendige for at tjenesten skal fungere, blant annet en sesjons­cookie for innlogging og en CSRF‑token som beskytter mot forfalskede skjema­innsendelser. Disse kan ikke slås av uten at tjenesten slutter å fungere.</p>
<p class="mt-3">Se <a href="{{ route(\'legal\') }}#cookies" class="text-brand-300 hover:text-brand-200">cookie‑siden</a> for detaljer.</p>',
        ],
        [
            'slug' => 'endringer',
            'title' => 'Endringer i personvernerklæringen',
            'body' =>
                '<p>Vi kan oppdatere denne personvern­erklæringen når tjenesten endres, eller ved nye lovkrav. Vesentlige endringer varsles på forhånd via e‑post eller i applikasjonen. Gjeldende versjon er alltid tilgjengelig på denne siden, og dato for siste oppdatering står øverst.</p>',
        ],
    ];
@endphp

@section('content')
    <section class="relative hero-gradient overflow-hidden">
        <div class="absolute inset-0 grid-pattern opacity-40"></div>
        <div class="relative max-w-5xl mx-auto px-6 lg:px-8 pt-20 pb-16 sm:pt-28 sm:pb-20 text-center">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-brand-400/20 bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-300">
                Personvern
            </span>
            <h1 class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                Personvern­erklæring
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                Hvordan Ekdahl Enterprises AS behandler personopplysninger i tjenesten Wealth Prognosis — hvilke data,
                hvorfor, rettslig grunnlag og dine rettigheter.
            </p>
            <p class="mt-6 text-xs uppercase tracking-wider text-slate-400">Sist oppdatert {{ $lastUpdated }}</p>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-12">
            <nav aria-label="Innholdsfortegnelse" class="flex flex-wrap gap-2">
                @foreach ($sections as $section)
                    <a href="#{{ $section['slug'] }}"
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.03] hover:border-brand-400/40 hover:bg-brand-500/10 px-4 py-2 text-sm text-slate-200 hover:text-white transition">
                        {{ $section['title'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 pb-20 sm:pb-28 space-y-6">
            @foreach ($sections as $section)
                <article id="{{ $section['slug'] }}"
                    class="scroll-mt-24 rounded-2xl border border-white/5 bg-white/[0.02] p-6 sm:p-8">
                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">{{ $section['title'] }}</h2>
                    <div class="mt-4 text-sm sm:text-base text-slate-300 leading-relaxed">
                        {!! $section['body'] !!}
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endsection

@push('head')
    @php
        $privacySchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'PrivacyPolicy',
                    '@id' => url()->current() . '#privacy',
                    'name' => 'Personvernerklæring — Wealth Prognosis',
                    'url' => url()->current(),
                    'inLanguage' => 'no',
                    'isPartOf' => ['@id' => url('/') . '#website'],
                    'publisher' => ['@id' => url('/') . '#organization'],
                    'dateModified' => $lastUpdated,
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Personvern', 'item' => route('personvern')],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($privacySchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
