---
template: use-cases
title: "Bruksområder — hvem Wealth Prognosis er for"
description: "Konkrete bruksområder for Wealth Prognosis: planlegging av F.I.R.E, eiendomsporteføljer, drift av enmanns‑AS, sammenligning av tidlig vs. normal pensjonering, samt modellering av arv og overføring fra selskap til privat."
og_type: article

hero:
  badge: "Bruksområder"
  title_html: |
    Én motor,<br>mange økonomiske liv.
  lead: "Enten du sikter mot tidlig pensjon, drifter en eiendomsportefølje eller henter ut verdier fra et enmanns‑AS — Wealth Prognosis modellerer hele bildet, år for år, etter skatt."

labels:
  problem: "Problemet"
  how: "Slik håndterer Wealth Prognosis det"
  outcome: "Resultat"

cases:
  - slug: "fire"
    badge: "F.I.R.E"
    title: "Planlegging for økonomisk uavhengighet"
    audience: "Ansatte og selvstendige som vil pensjonere seg år før offisiell pensjonsalder."
    problem: "Et 4 %-regneark gir deg ett enkelt tall. Det tar ikke hensyn til norsk formueskatt, gevinstskatt ved fondssalg, trinnskatteffekter på pensjonsinntekt, eller at hus, hytte og bil ikke inngår i nedsalget."
    how:
      - "Konfigurer fødselsår, ønsket pensjonsår, offisielt pensjonsår og forventet dødsår."
      - "Legg inn hver eiendel med realistiske endringsrater — aksjefond, ASK, pensjon, bank, eiendom."
      - "Marker likvide vs. ikke-likvide. Motoren selger ned likvide eiendeler jevnt fra pensjon til død."
      - "Kjør tre-scenario-simuleringen. Se om det pessimistiske scenarioet fortsatt dekker utgiftene."
      - "Spør AI-en: \"hvor mye må jeg spare i aksjefondet per måned for å kunne gå av ved 55?\""
    outcome: "En år-for-år-visning av nettoformue, kontantstrøm, skatt og FIRE-progresjon under tre markedsregimer — ikke bare ett optimistisk overskrift­tall."

  - slug: "property"
    badge: "Eiendomsinvestor"
    title: "Drift av en eiendomsportefølje"
    audience: "Private utleiere og eiendomsinvestorer med primærbolig pluss én eller flere utleieenheter."
    problem: "Å spore netto avkastning etter kommunal eiendomsskatt, formueskatt på eiendomsverdi, utleieskatt, fradragsberettigede renter og eventuell gevinstskatt ved salg er tungvint — og endrer seg hvert år lånet nedbetales."
    how:
      - "Legg inn hver eiendom som egen eiendel med markedsverdi, lån, leieinntekt og vedlikehold."
      - "Bruk riktig kommunal eiendomsskatte­konfigurasjon (327 norske kommuner følger med appen)."
      - "La motoren beregne annuitets­amortisering, fradrags­renter og utleieskatt per år."
      - "Simuler salg av en utleieenhet i et fremtidig år — motoren bruker realisasjonsskatt og overfører nettoen til en annen eiendel."
      - "Sammenlign å beholde vs. å selge på tvers av pessimistisk, realistisk og optimistisk scenario."
    outcome: "Tydelig oversikt over om hver eiendom faktisk lønner seg etter skatt, og en plan du kan forsvare for når du bør selge eller refinansiere."

  - slug: "one-person-as"
    badge: "Enmanns-AS"
    title: "Hente ut verdi fra et aksjeselskap"
    audience: "Konsulenter og gründere som driver et norsk AS og må planlegge lønn vs. utbytte vs. tilbakeholdt overskudd."
    problem: "Du kan utbetale lønn (skattes som inntekt), utbytte (selskapsskatt, deretter utbytteskatt på nettoen, med skjermings­fradrag), eller bygge opp tilbakeholdt overskudd. Avveiningene forsterker seg over flere tiår."
    how:
      - "Modeller selskapet som en egen eiendelsgruppe med egen kontantstrøm og formueskatte­verdsettelse."
      - "Legg til lønnsregler som overfører fra selskap til privat med riktige inntekts­skatte­trinn."
      - "Legg til utbytteregler — motoren bruker først 22 % selskapsskatt, deretter utbytteskatt på nettoen over skjermings­fradraget."
      - "Simuler et \"overta som privat\"-hendelse i et fremtidig år og se hele realisasjons- og utbytte­stakken."
      - "Sammenlign strategier side om side: kun lønn, kun utbytte, blandet med tilbakeholdt overskudd."
    outcome: "En 20-årig prognose som viser hvilken uthentings­strategi som gir deg mest formue etter skatt — ikke bare i år, men hvert år."

  - slug: "retirement-timing"
    badge: "Pensjonstidspunkt"
    title: "Tidlig, normal eller utsatt pensjonering"
    audience: "Alle innen ti år fra pensjon som lurer på hvilket år som faktisk gir mest mening."
    problem: "Folketrygden, AFP, tjenestepensjon og privat sparing slår inn på ulike datoer og beskattes forskjellig. Små endringer i når du starter hver av dem kan flytte livstids­formuen med sekssifrede beløp."
    how:
      - "Sett tre ulike ønskede pensjonsår og kjør tre parallelle konfigurasjoner."
      - "La motoren sekvensere folketrygd, OTP og privat sparing automatisk basert på konfigurerte startår."
      - "Følg kontantstrøm- og nettoformue­kurver for hvert scenario på samme akse."
      - "Identifiser året der utsatt pensjon ikke lenger er verdt det — som regel når helse eller tid blir den bindende begrensningen."
    outcome: "Et direkte, tallfestet svar på spørsmålet \"hvor mye koster det meg å gå av tre år tidligere?\""

  - slug: "inheritance"
    badge: "Husholdnings­planlegging"
    title: "Barnekostnader, barnetrygd og arv"
    audience: "Husholdninger med barn hjemme eller en kjent fremtidig arvehendelse."
    problem: "Barn er negativ kontantstrøm til de flytter ut; deretter ikke. Arv lander i et fremtidig år med sin egen skattebehandling. Begge hendelsene forstyrrer langtidsplaner hvis de modelleres som et flatt gjennomsnitt."
    how:
      - "Legg til hvert barn som en eiendel med inntekt (barnetrygd), utgifter og et \"fjernet fra økonomien\"-år."
      - "Modeller en arvehendelse i et fremtidig år med forventet verdi og skattebehandling."
      - "La motoren beregne kontantstrøm- og nettoformue­endringer før og etter automatisk."
      - "Scenario-test hva som skjer hvis arven utsettes eller reduseres."
    outcome: "Et ærlig bilde av økonomien gjennom og etter barneårene — og en plan som ikke kollapser når tidslinjen forskyver seg."

  - slug: "advisors"
    badge: "Rådgivere"
    title: "Rådgivning for flere klienter"
    audience: "Uavhengige finansielle rådgivere, regnskaps­førere og family office-operatører."
    problem: "Hver klient har ulik portefølje, skattesituasjon og tidslinje. Å vedlikeholde regneark per klient er sårbart og tregt å oppdatere når skatte­reglene endres."
    how:
      - "Ett team per klient — data er fullt isolert via multi-tenant team-scoping."
      - "Felles endringsrate-konfigurasjoner slik at antakelser er konsistente på tvers av kundebasen din."
      - "Eksporter år-for-år-Excelen for å sende til klienten etter hvert møte."
      - "Bruk AI-assistenten på norsk eller engelsk for å gjøre raske konfigurasjons­justeringer live."
    outcome: "Ett system for å vedlikeholde antakelsene dine, kjøre hver klients simulering på minutter og levere et profesjonelt eksporterbart dokument."

closing:
  heading: "Står ikke ditt tilfelle på listen?"
  lead: "Motoren er konfigurasjons­drevet. Hvis du kan beskrive en eiendel, en inntektsstrøm eller en skattehendelse, kan du modellere den."
  cta_primary: "Prøv det med dine egne data"
  cta_secondary: "Les FAQ"

schema:
  name: "Bruksområder for Wealth Prognosis"
---
