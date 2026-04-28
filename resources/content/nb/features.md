---
template: features
title: "Funksjoner — Wealth Prognosis"
description: "Komplett funksjonsoversikt: år-for-år prognose, beskatning, FIRE-måltall, AI-assistent, multi-tenant, modellering av boliglån og mer."
og_type: website

hero:
  badge: "Funksjoner"
  title_html: |
    Alt Wealth Prognosis gjør,<br>på ett sted.
  lead: "En komplett planleggingsplattform som dekker prognose, beskatning, FIRE, AI, boliglån og mer — bygget for langsiktig økonomisk klarhet."

groups_label: "Funksjonsgrupper"

groups:
  - title: "Prognosemotor"
    intro: "Beregningskjernen simulerer hvert år mellom i dag og forventet dødsår."
    items:
      - { name: "År-for-år simulering", body: "Inntekter, utgifter, boliglån, kontantstrøm, skatt og eiendelsverdi beregnet per år for hver eiendel du eier." }
      - { name: "Tre scenarier", body: "Kjør den samme konfigurasjonen som pessimistisk, realistisk og optimistisk — endringsrater kan konfigureres per eiendel." }
      - { name: "Overføringer mellom eiendeler", body: "Flytt kontantstrøm eller eiendelsverdi fra én eiendel til en annen med korrekt beskatning ved realisasjon." }
      - { name: "Regelbaserte tillegg", body: "Legg til faste beløp, prosenter av andre eiendeler, eller avledede verdier (f.eks. 5 % av lønn til OTP)." }
      - { name: "Repetisjons- og milepælsår", body: "Bruk $pensionWishYear, $deathYear, $pensionOfficialYear som symbolske år som tilpasser seg livsplanen din." }
      - { name: "Excel-eksport", body: "Eksportér hele prognosen til Excel med ark per eiendel, ark per type og et totalark." }

  - title: "Beskatning"
    intro: "Komplett dekning av norske skatteregler, år for år. Svensk og sveitsisk skatt er tilgjengelig i beta."
    items:
      - { name: "Formuesskatt", body: "Beregnet per år basert på aggregert nettoformue, med trinnvise satser fra skattekonfigurasjonen." }
      - { name: "Eiendomsskatt", body: "Satser per kommune, inkludert skattefritt grunnlag og bunnfradrag." }
      - { name: "Inntekts- og kapitalskatt", body: "Lønn, kapitalgevinster, renter og utbyttebeskatning med korrekte trinn og fradrag." }
      - { name: "Utleie- og selskapsskatt", body: "Utleieinntekt, selskapsskatt og utbytteskatt på utdelinger til privat." }
      - { name: "Skjermingsfradrag", body: "Korrekt skjerming av utbytte og kapitalgevinster mot grunnsatsen." }
      - { name: "Realisasjons- og overføringsskatt", body: "Korrekt beskatning ved realisasjon av eiendeler i selskap før overføring til privat." }
      - { name: "Sverige og Sveits (beta)", body: "Kjernen i svensk beskatning (kapitalinkomst, ISK/KF schablonskatt, kapitalvinst, statlig &amp; kommunal inkomstskatt) og sveitsisk beskatning (føderal + kantonal inntekt, formuesskatt, Säule 3a) er brukbar i dag i beta." }

  - title: "Boliglån og lån"
    intro: "Full modellering av boliglån med fradragsberettigede renter."
    items:
      - { name: "Annuitetslån", body: "Terminbeløp, renter, avdrag, gebyr og saldo beregnet per år for hele lånets levetid." }
      - { name: "Ekstra avdrag", body: "Overfør kontantstrøm til boliglånet for å redusere hovedstol — motoren omberegner gjenværende år." }
      - { name: "Fradragsberettigede renter", body: "Fradragsbeløp, sats og prosent spores per år og mates tilbake til inntektsskatten." }
      - { name: "Maks-lån-beregning", body: "Se hvor mye du kan låne basert på inntekt, eksisterende gjeld og eiendomsverdi." }

  - title: "FIRE-måltall"
    intro: "Ikke 4 %-regelen — en nedsalgsstrategi som beholder dine essensielle eiendeler."
    items:
      - { name: "Finansiell uavhengighetsår", body: "Se nøyaktig når dine likvide eiendeler kan dekke gjenværende livstidsutgifter." }
      - { name: "Nedsalgssimulering", body: "Realisér likvide eiendeler til null gjennom pensjonisttilværelsen — med skatt ved hver realisasjon." }
      - { name: "Essensielle eiendeler beholdes", body: "Hus, hytte, bil og båt forblir hos deg; simuleringen selger kun det du markerer som likvid." }

  - title: "AI-assistent"
    intro: "Naturlig språk-tilgang til hele din økonomiske konfigurasjon."
    items:
      - { name: "Engelsk og norsk", body: "&quot;Legg til en tesla til verdi 200K med lån 100K over 7 år&quot; eller &quot;Set my house value to 3.5M NOK&quot; — begge fungerer." }
      - { name: "Drevet av Gemini", body: "Google Gemini under panseret; samtalehistorikk lagres per bruker så assistenten har kontekst mellom spørsmål." }
      - { name: "Verktøykall", body: "AI-en kan opprette eiendeler, oppdatere boliglån, justere verdier og forklare skatteberegninger gjennom trygge, avgrensede verktøy." }
      - { name: "Hva-hvis-analyse", body: "Spør &quot;hva om jeg betaler ned boliglånet med 10K/måned?&quot; og få en forklart sammenligning." }

  - title: "Multi-tenant og sikkerhet"
    intro: "Dataene dine tilhører ditt team og ingen andre."
    items:
      - { name: "Team-isolerte data", body: "Et globalt scope filtrerer hver spørring etter team_id — eiendelene dine vises aldri i en annen brukers dashbord." }
      - { name: "Revisjonsspor", body: "created_by, updated_by, created_checksum og updated_checksum på hver modell, vedlikeholdes automatisk." }
      - { name: "Signerte nedlastings-URL-er", body: "Analysefiler serveres gjennom signerte, autentiseringsbeskyttede ruter." }
      - { name: "Moderne admin", body: "Rask admin-UI med ressurstabeller, skjemaer, filtre og masseoperasjoner." }

  - title: "Eiendeler og konfigurasjon"
    intro: "15+ eiendelstyper som dekker alt du kan eie."
    items:
      - { name: "Eiendom", body: "Hus, hytter, utleieeiendommer med eiendomsskatt, vedlikehold og leieinntekter." }
      - { name: "Kjøretøy", body: "Biler, båter, motorsykler med avskrivning, forsikring og driftskostnader." }
      - { name: "Pensjon", body: "OTP, IPS, folketrygd og private pensjoner med korrekt skattebehandling." }
      - { name: "Finansielle eiendeler", body: "Aksjer, obligasjoner, fond, ETF-er, krypto, kontanter — hver med egen skatteprofil." }
      - { name: "Selskap", body: "Eierskap i AS/ASA med utbytte-, inntekts- og overføringsbeskatning." }
      - { name: "Lån og gjeld", body: "Boliglån, studielån, kredittlinjer — alt modellert som negative eiendeler." }

closing:
  heading: "Klar til å se fremtiden din?"
  lead: "Åpne admin-dashbordet, konfigurér din første eiendel, og kjør simuleringen."
  cta_primary: "Åpne dashbord"
  cta_secondary: "Tilbake til hjem"

schema:
  feature_list:
    - { name: "Prognosemotor", description: "År-for-år simulering av inntekter, utgifter, boliglån, kontantstrøm, skatt og eiendelsverdi i pessimistiske, realistiske og optimistiske scenarier." }
    - { name: "Beskatning", description: "Komplett dekning av formuesskatt, eiendomsskatt, inntektsskatt, kapitalskatt, utleieskatt, selskapsskatt, utbytteskatt og skjermingsfradrag. Svensk og sveitsisk skatt er tilgjengelig i beta." }
    - { name: "Boliglån og lån", description: "Annuitetslån, ekstra avdrag, fradragsberettigede renter og maks-lån-beregning." }
    - { name: "FIRE-måltall", description: "Nedsalgsstrategi for likvide eiendeler mens essensielle eiendeler som hus, hytte, bil og båt beholdes." }
    - { name: "AI-assistent", description: "Naturlig språk-tilgang på engelsk og norsk til hele konfigurasjonen via Google Gemini med verktøykall." }
    - { name: "Multi-tenant og sikkerhet", description: "Team-isolerte data, revisjonsspor, signerte nedlastings-URL-er og moderne admin-UI." }
    - { name: "Eiendeler og konfigurasjon", description: "15+ eiendelstyper som dekker eiendom, kjøretøy, pensjon, finansielle eiendeler, selskap og gjeld." }
---
