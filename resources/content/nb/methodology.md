---
template: methodology
title: "Metodikk — hvordan hver avansert widget beregnes"
description: "Den nøyaktige matematikken bak hver avansert widget i Wealth Prognosis — FIRE-progresjon, krysningspunkt, sikker uttaksrate, pensjonsberedskap, sparerate, nettoformue-prognose og beskatning — skrevet som LaTeX-formler med gjennomarbeidede eksempler."
og_type: article

hero:
  badge: "Metodikk"
  title_html: |
    Matematikken bak<br>hvert tall.
  lead: "Ingen sorte bokser. Hver avansert widget i Wealth Prognosis er dokumentert her med eksakt formel, inndata og et gjennomarbeidet eksempel — slik at du kan stole på resultatet, etterprøve det og reprodusere det."
  cta_primary: "Hopp til formlene"
  cta_secondary: "Les ordlisten"

labels:
  inputs: "Inndata"
  caveats: "Forbehold"
  formula: "Formel"
  example: "Gjennomarbeidet eksempel"

pillars:
  - title: "Tre scenarioer, alltid"
    body: "Hver formel kjøres tre ganger — pessimistisk, realistisk, optimistisk — med ulike endringsrater. Du ser hele utfallsspennet side om side."
  - title: "År for år, ikke gjennomsnitt"
    body: "Skatter, utgifter og avkastning beregnes for hvert år individuelt. Ingen utjevning, ingen enkelt overskriftstall som skjuler sannheten."
  - title: "Etterprøvbar fra design"
    body: "Hver beregnet rad bærer en SHA-256 sjekksum pluss opprettet/oppdatert-stempler. Du kan eksportere hele beregningen til Excel eller JSON når som helst."

widgets_section:
  heading: "Avanserte widgets"
  lead: "Widgetene som gjør reell matematikk — ikke bare summer og grupperinger. Hver lister inndata, eksakt formel, et gjennomarbeidet eksempel og forbeholdene vi vil at du skal kjenne."

widgets:
  - slug: "net-worth"
    title: "Nettoformue over tid"
    lede: "Overskriftstallet. Markedsverdien av alt du eier, minus alt du skylder, plottet år for år."
    inputs:
      - "asset_market_amount (per eiendel, per år)"
      - "mortgage_amount (per eiendel, per år)"
    formula: "\\[ NW_y \\;=\\; \\sum_{a \\in A_y} M_{a,y} \\;-\\; \\sum_{a \\in A_y} L_{a,y} \\]"
    legend: "\\(NW_y\\) nettoformue i år \\(y\\), \\(M_{a,y}\\) markedsverdi av eiendel \\(a\\), \\(L_{a,y}\\) lånebalanse, \\(A_y\\) alle aktive eiendeler for valgt konfigurasjon."
    example: "År 2024 — eiendeler totalt 8 450 000, lån totalt 3 200 000. \\(NW_{2024} = 8\\,450\\,000 - 3\\,200\\,000 = 5\\,250\\,000\\)."
    caveats: "Bruker dine faktiske registrerte verdier til og med inneværende år; fremtidige år kommer fra prognose-motoren, ikke denne widgeten."

  - slug: "fire-number"
    title: "FIRE-tall &amp; progresjon"
    lede: "Hvor mye formue du trenger for å pensjonere deg — og hvor langt du allerede er. Det viktigste enkelttallet på dashbordet."
    inputs:
      - "expence_amount (alle eiendeler, inneværende år)"
      - "asset_market_amount (likvid + bevart, inneværende år)"
    formula: "\\[ F \\;=\\; 25 \\times E \\qquad\\quad p \\;=\\; \\min\\!\\left(\\tfrac{P}{F},\\, 1\\right) \\]"
    legend: "\\(F\\) FIRE-tall, \\(E\\) årlige utgifter, \\(P\\) nåværende porteføljeverdi, \\(p\\) progresjon mot FIRE (0 til 1)."
    example: "Årlige utgifter 540 000, portefølje 7 200 000. \\(F = 25 \\times 540\\,000 = 13\\,500\\,000\\); \\(p = 7\\,200\\,000 / 13\\,500\\,000 \\approx 53.3\\%\\)."
    caveats: "25×-multiplikatoren er det inverse av 4 %-uttaksregelen. For en mer konservativ plan, øk multiplikatoren (30× ⇒ 3,33 % SWR)."

  - slug: "fire-crossover"
    title: "FIRE-krysningspunkt"
    lede: "Øyeblikket porteføljen din kan betale for livet ditt fra passiv inntekt alene. Etter dette er du, i prinsippet, fri."
    inputs:
      - "asset_market_amount (inneværende år)"
      - "expence_amount (inneværende år)"
    formula: "\\[ \\text{krysning} \\;\\Longleftrightarrow\\; 0.04 \\cdot P \\;\\geq\\; E \\]"
    legend: "\\(P\\) nåværende portefølje, \\(E\\) årlige utgifter. Konstanten 0,04 er den klassiske 4 %-uttaksraten."
    example: "Portefølje 15 000 000, utgifter 540 000. Passiv inntekt \\(0.04 \\times 15\\,000\\,000 = 600\\,000 \\geq 540\\,000\\) ⇒ krysning oppnådd."
    caveats: "Binær indikator — ikke en nedsalgs-simulering. For år-for-år uttaksgjennomførbarhet på tvers av tre scenarioer kjører den fulle prognose-motoren en faktisk likvidering mot dine faktiske eiendeler og skatter."

  - slug: "fire-metrics"
    title: "FIRE-måltall over 30 år"
    lede: "Projiserer nettoformue 30 år frem mot et inflasjonsjustert FIRE-mål, slik at du ser året de to linjene krysser."
    inputs:
      - "nåværende nettoformue"
      - "årlig sparing (\\(I - E\\))"
      - "vekstrate \\(r = 7\\%\\)"
      - "inflasjon \\(\\pi = 3\\%\\)"
    formula: "\\[ P_{t+1} \\;=\\; (P_t + S)(1 + r), \\qquad F_t \\;=\\; F_0 \\cdot (1 + \\pi)^t \\]"
    legend: "\\(P_t\\) projisert portefølje i år \\(t\\), \\(S\\) årlig sparing (inntekt minus utgifter), \\(r\\) nominell vekstrate, \\(F_t\\) FIRE-tall inflatert fra \\(F_0\\) med \\(\\pi\\)."
    example: "Start \\(P_0 = 5\\,000\\,000\\), \\(S = 300\\,000\\). Etter ett år \\(P_1 = (5\\,000\\,000 + 300\\,000) \\times 1.07 = 5\\,671\\,000\\). Etter ti år \\(P_{10} \\approx 14\\,020\\,000\\)."
    caveats: "Bruker konstant \\(r\\) og \\(\\pi\\) for lesbarhet. Hovedprognose-motoren kjører samme projeksjon per eiendel, per år, på tvers av tre scenarioer med konfigurerbare endringsrater."

  - slug: "savings-rate"
    title: "Sparerate over tid"
    lede: "Den enkelt beste prediktoren for FIRE-tidslinjen din. En 50 % sparerate gir økonomisk uavhengighet i omtrent 17 år uavhengig av inntekt."
    inputs:
      - "income_amount (per år, inntektseiendeler)"
      - "expence_amount (per år, alle eiendeler)"
    formula: "\\[ s_y \\;=\\; \\frac{I_y - E_y}{I_y} \\]"
    legend: "\\(s_y\\) sparerate i år \\(y\\), \\(I_y\\) total inntekt, \\(E_y\\) totale utgifter. Uttrykkes i prosent. Referanselinje tegnet på 20 %."
    example: "Inntekt 900 000, utgifter 540 000. \\(s = (900\\,000 - 540\\,000) / 900\\,000 = 40\\%\\)."
    caveats: "Kun historiske år — widgeten projiserer aldri inn i fremtiden. Negativ når utgifter overstiger inntekt (uttak fra kapital)."

  - slug: "retirement-readiness"
    title: "Pensjonsberedskap"
    lede: "Projiserer dagens nettoformue til planlagt pensjonsalder mot et kapital-tilstrekkelighetsmål, basert på dine egne utgifter."
    inputs:
      - "nåværende nettoformue"
      - "birth_year, pension_wish_year, death_year"
      - "årlig inntekt, årlige utgifter"
    formula: "\\[ T \\;=\\; 25 \\times 0.80 \\times E \\qquad\\quad NW_{t} \\;=\\; (NW_{t-1} + S)(1+r) \\]"
    legend: "\\(T\\) pensjonsmål (25× av 80 % av nåværende utgifter — den klassiske 70–80 % inntektserstatningsregelen), \\(NW_t\\) projisert nettoformue ved alder \\(t\\), \\(r\\) antatt vekst (standard 7 %)."
    example: "Utgifter 540 000 ⇒ \\(T = 25 \\times 0.80 \\times 540\\,000 = 10\\,800\\,000\\). Med 3 000 000 ved 40 år og 300 000 årlig sparing, \\(NW_{65} \\approx 23\\,100\\,000\\) — komfortabelt over målet."
    caveats: "80 %-erstatningsforholdet er en mye brukt tommelfingerregel, ikke en personlig prognose. Pensjonsutbetalinger fra tjenestepensjon/IPS/offentlig pensjon modelleres separat av skattemotoren."

  - slug: "actual-tax-rate"
    title: "Faktisk effektiv skattesats"
    lede: "Din reelle skattebyrde — hver skatt motoren beregnet, delt på skattbart grunnlag. Ikke en overskriftssats — satsen du faktisk betaler."
    inputs:
      - "income_tax"
      - "fortune_tax"
      - "property_tax"
      - "capital_gains_tax"
      - "taxable_income_base"
    formula: "\\[ \\tau_y \\;=\\; \\frac{T^{\\text{income}}_y + T^{\\text{fortune}}_y + T^{\\text{property}}_y + T^{\\text{gains}}_y}{B_y} \\]"
    legend: "\\(\\tau_y\\) effektiv skattesats i år \\(y\\), \\(T^{\\star}_y\\) skatten betalt av hver type, \\(B_y\\) skattbart grunnlag (brutto inntekt + realiserte gevinster)."
    example: "Brutto grunnlag 950 000, totale skatter 278 400. \\(\\tau = 278\\,400 / 950\\,000 \\approx 29.3\\%\\)."
    caveats: "Formueskatt og eiendomsskatt er formuesbaserte, men inkluderes i telleren fordi de er en reell kontant utbetaling. Forholdet er ikke direkte sammenlignbart med en marginal inntektsskattesats."

taxation_section:
  heading: "Beskatning"
  lead: "Skattemotoren modellerer de virkelige trinnene, tersklene og skjermings­reglene — ikke grove prosenter. Satser, bånd og kommunale regler lastes per år fra skattekonfigurasjons­tabellene."

tax_formulas:
  - title: "Formueskatt"
    formula: "\\[ T^{\\text{formue}} \\;=\\; \\max\\!\\bigl(0,\\; W_{\\text{netto}} - W_{\\text{terskel}}\\bigr) \\cdot \\bigl(r_{\\text{stat}} + r_{\\text{kommune}}\\bigr) \\]"
    legend: "\\(W_{\\text{netto}}\\) verdsatt nettoformue (primærbolig, aksjer, driftsmidler får hver sin verdsettelses­rabatt), \\(W_{\\text{terskel}}\\) årlig terskel, \\(r_{\\text{stat}}\\) og \\(r_{\\text{kommune}}\\) statlige og kommunale satser."
  - title: "Trinnskatt"
    formula: "\\[ T^{\\text{trinn}} \\;=\\; \\sum_{k=1}^{K} r_k \\cdot \\max\\!\\bigl(0,\\; \\min(Y, b_{k+1}) - b_k\\bigr) \\]"
    legend: "\\(Y\\) brutto alminnelig inntekt, \\(b_k\\) nedre grense for trinn \\(k\\), \\(r_k\\) marginalsats for det trinnet. Trinn lastes per år fra skattekonfigurasjonen."
  - title: "Skjermingsfradrag"
    formula: "\\[ S_t \\;=\\; C_t \\cdot r^{\\text{skjerm}}_t, \\qquad T^{\\text{utbytte}} \\;=\\; \\max\\!\\bigl(0,\\; D_t - S_t\\bigr) \\cdot g \\cdot r^{\\text{kap}} \\]"
    legend: "\\(C_t\\) kostpris, \\(r^{\\text{skjerm}}_t\\) årlig risikofri skjermingsrate, \\(S_t\\) skjermingsfradrag, \\(D_t\\) mottatt utbytte, \\(g\\) oppgrossings­faktor (for tiden 1,72), \\(r^{\\text{kap}}\\) kapitalskattesats."
  - title: "Eiendomsskatt"
    formula: "\\[ T^{\\text{eiendom}} \\;=\\; \\max\\!\\bigl(0,\\; V \\cdot d - V_{\\text{terskel}}\\bigr) \\cdot r_{\\text{kommune}} \\]"
    legend: "\\(V\\) markedsverdi for eiendommen, \\(d\\) kommunal verdsettelses­rabatt (ofte 0,70), \\(V_{\\text{terskel}}\\) kommunalt bunnfradrag, \\(r_{\\text{kommune}}\\) kommunal sats. 327 kommuner ferdig konfigurert."

prognosis_section:
  heading: "Prognose-matematikk"
  lead: "Primitivene som ruller hver eiendel fremover, år etter år, på tvers av tre scenarioer."

prognosis:
  - title: "Årlig sammensatt rull-frem"
    formula: "\\[ V_{y+1} \\;=\\; V_y \\cdot \\bigl(1 + c_{y,s}\\bigr) \\;+\\; \\Delta_{y,s} \\]"
    legend: "Hver eiendel utvikler seg år for år. \\(V_y\\) verdi i år \\(y\\), \\(c_{y,s}\\) prosentvis endringsrate for år \\(y\\) under scenario \\(s\\), \\(\\Delta_{y,s}\\) faste justeringer (påfyll, overføringer, regelmotor-mutasjoner)."
  - title: "Compound Annual Growth Rate (CAGR)"
    formula: "\\[ \\text{CAGR} \\;=\\; \\left(\\frac{V_{\\text{slutt}}}{V_{\\text{start}}}\\right)^{\\!1/n} - 1 \\]"
    legend: "Utjevnet annualisert vekst mellom to tidspunkter. \\(n\\) er antall år. Brukt i simulerings­sammendrag og eiendels­oversiktskortet."
  - title: "Reell vs. nominell avkastning"
    formula: "\\[ r_{\\text{reell}} \\;=\\; \\frac{1 + r_{\\text{nominell}}}{1 + \\pi} - 1 \\]"
    legend: "Konverterer en nominell avkastning til reell (inflasjons­justert) avkastning ved bruk av KPI \\(\\pi\\). Motoren viser begge; utgifter og skatteterskler inflateres med samme \\(\\pi\\)."
  - title: "Annuitets­låneterminbeløp"
    formula: "\\[ A \\;=\\; L \\cdot \\frac{r\\,(1+r)^{n}}{(1+r)^{n} - 1} \\]"
    legend: "\\(A\\) årlig betaling, \\(L\\) gjenværende lån, \\(r\\) periodisk rente, \\(n\\) gjenværende løpetid. Hvert år splittes i rente (fradragsberettiget) og avdrag."

closing:
  heading: "Kjør tallene på ditt eget liv."
  lead: "Hver formel på denne siden kjører live mot dine egne eiendeler, inntekter, skatter og scenarioer i det øyeblikk du logger inn. Ingen regneark, ingen gjetning."
  cta_primary: "Åpne dashbordet"
  cta_secondary: "Bla i funksjoner"

schema:
  headline: "Metodikk — hvordan hver avansert widget beregnes"
  description: "Den nøyaktige matematikken bak hver avansert widget i Wealth Prognosis, med LaTeX-formler og gjennomarbeidede eksempler."
  about:
    - "FIRE-tall"
    - "Sikker uttaksrate"
    - "Pensjonsberedskap"
    - "Sparerate"
    - "Formueskatt"
    - "Trinnskatt"
    - "Skjermingsfradrag"
    - "CAGR"
---
