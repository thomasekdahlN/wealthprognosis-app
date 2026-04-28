---
template: faq
title: "FAQ — Wealth Prognosis"
description: "Svar på de vanligste spørsmålene om Wealth Prognosis — hvordan simuleringen fungerer, hvilke skatter som støttes, hvordan AI-assistenten oppfører seg, personvern, priser og mer."
og_type: website

hero:
  badge: "Ofte stilte spørsmål"
  title_html: |
    Spørsmål,<br>besvart.
  lead: "Alt folk vanligvis vil vite om Wealth Prognosis — motoren, skattene, AI-en og dataene dine."

groups:
  - title: "Det grunnleggende"
    items:
      - q: "Hva er Wealth Prognosis?"
        a: |
          <p>Et system for økonomisk planlegging og simulering som sporer hver eiendel du eier, bruker korrekt beskatning og simulerer økonomien din år for år — fra i dag og frem til forventet dødsår.</p><p class="mt-3">Du kan kjøre pessimistisk, realistisk og optimistisk scenario side om side, se nøyaktig når du kan pensjonere deg, og spørre en AI-assistent om å forklare eller justere konfigurasjonen din på vanlig språk.</p>
      - q: "Hvem er det for?"
        a: |
          <p>De som tenker langsiktig og vil ha klarhet i økonomien sin over flere tiår — ikke bare denne måneden. Det er spesielt sterkt for de som planlegger tidlig pensjon (FIRE), optimerer skatt, eller modellerer hvordan en lånestrategi spiller seg ut over 20–30 år.</p>
      - q: "Må jeg være utvikler for å bruke det?"
        a: |
          <p>Nei. Alt administreres gjennom admin-dashbordet — å legge til eiendeler, kjøre simuleringer, eksportere til Excel. Kommandolinjen og JSON-konfigurasjonene er der hvis du vil ha dem, men ikke påkrevd.</p>
      - q: "Hva koster det?"
        a: |
          <p>Hostede planer starter på 79 NOK / måned for én bruker, med nivåer for husholdninger, rådgivere, bedrifter og enterprise. Hver hostet plan inkluderer en 30-dagers prøveperiode. Se <a href="/nb/pricing" class="text-brand-300 hover:text-brand-200 underline underline-offset-2">prissiden</a> for detaljer.</p>

  - title: "Simulering &amp; beregninger"
    items:
      - q: "Hvor langt inn i fremtiden går simuleringen?"
        a: |
          <p>Fra i dag og frem til dødsåret du konfigurerer. Hvert år imellom beregnes individuelt — inntekt, utgifter, lån, skatt, eiendelsverdi og kontantstrøm.</p>
      - q: "Hva er forskjellen mellom pessimistisk, realistisk og optimistisk?"
        a: |
          <p>Hvert scenario bruker sin egen endringsrate for hver eiendel (f.eks. aksjevekst, eiendomsverdi-stigning, inflasjon). Samme konfigurasjon produserer tre parallelle prognoser slik at du ser et spenn i stedet for ett optimistisk tall.</p>
      - q: "Hvor nøyaktige er FIRE-tallene?"
        a: |
          <p>Mer nøyaktige enn en ren "4 %-regel"-beregning, fordi motoren gjør en faktisk nedsalgs-simulering: dine likvide eiendeler likvideres ned til null over pensjonsperioden, og hver realisasjon beskattes korrekt. Essensielle eiendeler du markerer som ikke-likvide (hus, hytte, bil, båt) beholdes.</p>
      - q: "Kan jeg modellere ekstra nedbetalinger på lånet?"
        a: |
          <p>Ja. Du kan overføre kontantstrøm fra én eiendel til et lån — motoren beregner gjenværende år og rente automatisk, og fører den reduserte fradragsberettigede renten tilbake i inntektsskatten.</p>

  - title: "Beskatning"
    items:
      - q: "Hvilke norske skatter støttes?"
        a: |
          <p>Formueskatt, eiendomsskatt, inntektsskatt, gevinstskatt, pensjonsskatt, utleieskatt, selskapsskatt, utbytteskatt og skjermingsfradrag. Alt beregnes per år, per eiendel, med riktige trinn.</p>
      - q: "Hva med andre land?"
        a: |
          <p>Norge er det fullt støttede standardvalget i dag. <strong class="text-white">Sverige og Sveits er tilgjengelige i beta</strong> — kjernetrinn, formueskatt og gevinstskatt-regler er modellert og brukbare, men noen kanttilfeller (kommunale variasjoner, kantonale forskjeller, pensjonsspesifikke regler) finpusses fortsatt. Tilbakemeldinger fra beta-brukere former hva som lanseres neste gang.</p><p class="mt-3">Skattemotoren er konfigurasjonsdrevet, så flere jurisdiksjoner kan legges til. Enterprise-kunder kan be om tilpassede skattekonfigurasjoner.</p>
      - q: "Håndterer den selskap-til-privat-overføringer korrekt?"
        a: |
          <p>Ja — motoren realiserer selskapseide eiendeler før distribusjon og bruker riktige skattelag (selskapsskatt, deretter utbytteskatt på beløpet som overføres til privat).</p>

  - title: "AI-assistent"
    items:
      - q: "Hva kan jeg spørre AI-en om?"
        a: |
          <p>Hva som helst om konfigurasjonen din. "Legg til en Tesla til verdi 200K med lån 100K over 7 år", "sett husverdien min til 3,5 millioner kroner", eller "hva skjer hvis jeg nedbetaler lånet med 10 000 per måned i tre år?" — assistenten kan både forklare tall og endre konfigurasjonen din gjennom trygge, avgrensede verktøy.</p>
      - q: "Hvilken AI-modell brukes?"
        a: |
          <p>Google Gemini. Samtale-historikk lagres per bruker slik at assistenten har kontekst mellom spørsmål.</p>
      - q: "Får AI-en noen gang se andre brukeres data?"
        a: |
          <p>Nei. Hvert AI-verktøy kjører gjennom de samme team-scopede spørringene som resten av appen, så assistenten kan bare se og endre eiendeler som tilhører det innloggede teamet.</p>

  - title: "Personvern &amp; hosting"
    items:
      - q: "Hvor lagres dataene mine?"
        a: |
          <p>Hostede planer kjører på sikker EU-infrastruktur med krypterte sikkerhetskopier. Du kan eksportere alt til JSON eller Excel når som helst — dataene tilhører deg.</p>
      - q: "Er dataene multi-tenant?"
        a: |
          <p>Ja. Et globalt scope filtrerer hver spørring etter <code class="text-brand-300">team_id</code>, og hver modell har audit-stempling (<code class="text-brand-300">created_by</code>, <code class="text-brand-300">updated_by</code>, sjekksummer). Dataene dine blandes aldri med et annet teams.</p>
      - q: "Kan jeg eksportere alt?"
        a: |
          <p>Ja. Full Excel-eksport av prognosen, ark per eiendel, ark per type og et totalark. Du eier dataene dine.</p>

  - title: "Komme i gang"
    items:
      - q: "Hvordan prøver jeg det?"
        a: |
          <p>Åpne <a href="/admin" class="text-brand-300 hover:text-brand-200 underline">dashbordet</a>, logg inn, og legg til din første eiendel. <a href="/nb/features" class="text-brand-300 hover:text-brand-200 underline">Funksjonssiden</a> viser alt motoren kan gjøre.</p>

closing:
  heading: "Har du fortsatt spørsmål?"
  lead: "Prøv dashbordet og se selv, eller ta kontakt med teamet."
  cta_primary: "Åpne dashbord"
  cta_secondary: "Se priser"
---
