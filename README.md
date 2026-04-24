<meta name="google-site-verification" content="sVU5VtIVFfRVyzYylywIA2XMMxLQez6ehHqDndMjI1M" />
<img src="public/logo.png" alt="Logo" width="200"/>

# Wealth Prognosis

> A comprehensive financial planning and simulation system — track all your assets, run year-by-year prognoses to your death date, and get AI-powered insights on your economic future.

## Table of contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Technology stack](#technology-stack)
- [Getting started](#getting-started)
- [Environment variables](#environment-variables)
- [Database setup](#database-setup)
- [Artisan commands](#artisan-commands)
- [Seeding](#seeding)
- [Deployment](#deployment)
- [Dashboards](#dashboards)
- [Asset types](#asset-types)
- [Configuration reference](#configuration-reference)
- [Output reference](#output-reference)
- [Licence](#licence)

---

## Overview

**Wealth Prognosis** predicts your yearly future economy from now until you die.

Makes a qualified prognosis of your economic future year for year until your death. It takes into account your income, expences, assets/liabilities, loans, taxes, inflation and more.

Configure all your assets, mortgage, income and expences.

The program takes into account all known taxes in Norway, like fortune tax, property tax, income tax, capital tax, pension tax, rental tax, company tax, dividend tax, interest tax, wealth tax, inheritance tax, gift tax, sales tax and tax shield and calculates it for every asset every year
The program looks at how much your max possibel mortgage can be

The program looks at different F.I.R.E metrics so you can see when you can become financially independent and retire early. But recommends a different approach than the 4% rule - it will take the number of years from you wish to retire until the year you die - and sell the liquid assets until zero (fully configurable). You will still have your house, car, boat and cabin left after these sales.
Supports correct taxation when transfering an asset from a company to private (first correct taxation on realization in company, then correct taxation when transfering to private)

Supports normal annuitets mortgage and extra downpayments.

On each asset you can do a rule based addition or subtraction, like adding 5000 to a equity fund every month.
On each asset you can do an calculation based on other assets value and add it to this asset. Like taking 5% of the salary and add it to OTP (this does not change your salary but it increases your OTP)
On each asset you can do an transfer to another asset. This will correctly calculate the taxes for the sale involved before transfer.

---

## Architecture

### System overview

```mermaid
graph TB
    subgraph UI["UI Layer (Filament 5 + Livewire 4)"]
        AP[Admin Panel /admin]
        DB[Dashboards]
        RC[Resources / CRUD]
        AI[AI Assistant Widget]
    end

    subgraph SVC["Services Layer"]
        PS[PrognosisService]
        PSS[PrognosisSimulationService]
        TAX[Tax Services\nIncome · Fortune · Property\nRealization · Salary]
        FIRE[FireCalculationService]
        AIS[AiAssistantService]
        EXP[Export Services\nExcel · JSON]
    end

    subgraph DATA["Data Layer (PostgreSQL)"]
        AC[asset_configurations]
        AS[assets]
        AY[asset_years]
        SC[simulation_configurations]
        SA[simulation_assets]
        SAY[simulation_asset_years]
        TC[tax_configurations]
        CR[change_rate_configurations]
        AI2[agent_conversations]
    end

    AP --> RC
    AP --> DB
    AP --> AI
    RC --> SVC
    DB --> SVC
    AI --> AIS
    PS --> DATA
    PSS --> PS
    TAX --> DATA
    FIRE --> DATA
    AIS --> AI2
    SVC --> DATA
```

### Multi-tenancy

```mermaid
graph LR
    U[User] -->|belongs to| T[Team]
    T -->|owns| AC[AssetConfiguration]
    AC -->|has many| A[Assets]
    A -->|has many| AY[AssetYears]
    AC -->|has many| SC[SimulationConfigurations]
    SC -->|has many| SA[SimulationAssets]
    SA -->|has many| SAY[SimulationAssetYears\ncalculated]

    TS[TeamScope\nglobal scope] -.->|filters all queries by team_id| AC
    TS -.-> A
    TS -.-> SC
```

All models with a `team_id` column are automatically filtered by the authenticated user's `current_team_id`. Tax configurations and reference data are global (not team-scoped).

### Simulation data flow

```mermaid
flowchart LR
    CFG[AssetConfiguration\n+ Assets + AssetYears]
    SIM[SimulationConfiguration\nprognosis type · group · country]
    CPY[Copy assets\nto simulation tables]
    CALC[PrognosisService\nyear-by-year calculations]
    RES[SimulationAssetYears\nfull calculated dataset]
    DASH[Dashboards\n& Excel exports]

    CFG --> SIM
    SIM --> CPY
    CPY --> CALC
    CALC -->|income · expense · mortgage\ntax · FIRE · metrics| RES
    RES --> DASH
```

### AI integration

```mermaid
graph LR
    W[AiAssistantWidget\nLivewire] --> AS[AiAssistantService]
    AS --> FA[FinancialAssistant\nAgent]
    FA -->|RemembersConversations| CV[agent_conversations\nagent_conversation_messages]
    FA -->|Gemini API| GM[gemini-3.1-flash-lite-preview]

    AE[AiEvaluationService] --> AN[AnonymousAgent]
    ACA[AiConfigurationAnalysisService] --> AN
    AN --> GM
```

---

## Technology stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13, PHP 8.5 |
| UI | Filament 5, Livewire 4, Alpine.js, Tailwind CSS |
| Database | PostgreSQL (production), SQLite (local/test) |
| AI | Laravel AI SDK (`laravel/ai`), Google Gemini |
| Testing | Pest 4, PHPUnit 12 |
| Code quality | Larastan level 9, Laravel Pint |
| Deployment | Laravel Cloud |
| Asset bundling | Laravel Mix |

---

## Getting started

```bash
# 1. Clone and install
git clone https://github.com/thomasekdahlN/wealthprognosis-app.git
cd wealthprognosis-app
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database
php artisan migrate
php artisan db:seed

# 4. Build assets
npm run dev       # development (watch)
npm run build     # production build

# 5. Start server
php artisan serve
```

Visit `http://localhost:8000/admin` and log in with the credentials from `UserSeeder`.

---

## Environment variables

```dotenv
APP_NAME="Wealth Prognosis"
APP_ENV=local
APP_KEY=                        # generated by php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost

# Database (PostgreSQL for production)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=wealthprognosis
DB_USERNAME=postgres
DB_PASSWORD=

# AI (Laravel AI SDK — Google Gemini)
AI_PROVIDER=gemini
GEMINI_API_KEY=                 # your Google AI Studio key
```

---

## Database setup

### Local (SQLite — quickstart)

```bash
touch database/database.sqlite
# DB_CONNECTION=sqlite in .env
php artisan migrate --seed
```

### Local (PostgreSQL via DBngin or Docker)

```bash
# DB_CONNECTION=pgsql, set host/port/db/user/password in .env
php artisan migrate --seed
```

### Production (Laravel Cloud)

Set the database environment variables in **Environment → Environment Variables** in the Cloud dashboard, then run migrations and seeds as one-off commands:

```bash
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
```

---

## Artisan commands

### Application commands

```mermaid
graph TD
    CMD[php artisan]

    CMD --> EXP[assets:export]
    CMD --> IMP[assets:import]
    CMD --> RF2[ReadFile2]
    CMD --> IPT[tax:import-norwegian-property]
    CMD --> SSB[tax:scrape-ssb-property]

    EXP --> |export by ID| E1[assets:export 123]
    EXP --> |export to path| E2[assets:export 123 --path=/tmp/out.json]
    EXP --> |export all| E3[assets:export --all]

    IMP --> |import for user| I1[assets:import file.json --user-id=1 --team-id=1]

    RF2 --> |run simulation to Excel| R1["ReadFile2 config.json realistic private"]
```

#### `assets:export`

Export one or all asset configurations to JSON.

```bash
php artisan assets:export {id}              # export single configuration
php artisan assets:export {id} --path=FILE  # export to specific path
php artisan assets:export --all             # export all configurations
```

Output files are named `YYYY-MM-DD-{name}.json`.

#### `assets:import`

Import a JSON configuration file into the database.

```bash
php artisan assets:import path/to/config.json
php artisan assets:import path/to/config.json --user-id=1 --team-id=1
```

#### `ReadFile2` *(legacy CLI runner)*

Run a prognosis directly from a JSON config file and generate an Excel output.

```bash
php artisan ReadFile2 {configfile} {prognosis} {generate}

# Arguments:
#   configfile   Path to JSON asset config
#   prognosis    realistic | positive | negative | tenpercent | zero | variable
#   generate     all | private | company

# Example:
php artisan ReadFile2 tests/Feature/config/example.json realistic private
```

Output: an Excel file saved next to the config file with the same base name.

#### `tax:import-norwegian-property`

Import Norwegian property tax rates from a local file.

#### `tax:scrape-ssb-property`

Scrape current property tax rates from Statistics Norway (SSB).

---

## Seeding

### Seeder overview

```mermaid
graph TD
    DS[DatabaseSeeder]
    DS --> US[UserSeeder\ndev users]
    DS --> TS[TeamSeeder\ndev team]
    DS --> TT[TaxTypesFromConfigSeeder\nreference data]
    DS --> AC[AssetCategorySeeder\nreference data]
    DS --> AT[AssetTypeSeeder\nreference data]
    DS --> AS[AssetConfigurationSeeder\ndev sample config]
    DS --> AI[AiInstructionSeeder\nAI prompts]
    DS --> TC[TaxConfigurationSeeder\nNorway tax rules]
    DS --> TP[TaxPropertySeeder\nproperty tax rates]
    DS --> PG[PrognosisSeeder\nprognosis types]
    DS --> CR[ChangeRateConfigurationSeeder\ngrowth rates]
    DS --> JI[JsonConfigImportSeeder\nsample JSON configs]
    DS --> SI[SimulationSeeder\nrun sample simulations]

    style US fill:#f9a,stroke:#c66
    style TS fill:#f9a,stroke:#c66
    style AS fill:#f9a,stroke:#c66
    style JI fill:#f9a,stroke:#c66
    style SI fill:#f9a,stroke:#c66
    style TT fill:#9f9,stroke:#393
    style AC fill:#9f9,stroke:#393
    style AT fill:#9f9,stroke:#393
    style AI fill:#9f9,stroke:#393
    style TC fill:#9f9,stroke:#393
    style TP fill:#9f9,stroke:#393
    style PG fill:#9f9,stroke:#393
    style CR fill:#9f9,stroke:#393
```

🟢 Green = safe reference data for production · 🔴 Red = development/sample data only

### Development (full seed)

```bash
php artisan db:seed
# or reset everything:
php artisan migrate:fresh --seed
```

### Production (reference data only)

Run individual seeders that are safe for production:

```bash
php artisan db:seed --class=TaxTypesFromConfigSeeder --force
php artisan db:seed --class=AssetCategorySeeder --force
php artisan db:seed --class=AssetTypeSeeder --force
php artisan db:seed --class=AiInstructionSeeder --force
php artisan db:seed --class=TaxConfigurationSeeder --force
php artisan db:seed --class=TaxPropertySeeder --force
php artisan db:seed --class=PrognosisSeeder --force
php artisan db:seed --class=ChangeRateConfigurationSeeder --force
```

### Creating users in production

Use Filament's built-in command (interactive, no hardcoded passwords):

```bash
php artisan make:filament-user
```

---

## Deployment

### Laravel Cloud

1. Push code to your repository
2. Connect the repository in [Laravel Cloud](https://cloud.laravel.com)
3. Set environment variables (see [Environment variables](#environment-variables))
4. Link or provision a PostgreSQL database
5. Deploy — Cloud runs `composer install`, `npm run build`, and `php artisan migrate --force` automatically

### One-off commands on Cloud

Run from **Environment → Commands** in the Cloud dashboard:

```bash
php artisan db:seed --class=TaxTypesFromConfigSeeder --force
php artisan make:filament-user
php artisan cache:clear
```

---

## Licence

The code is under GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007. Free for personal usage and free for non-commercial usage (that means that you do not make money on this software). Contributors wanted. Commercial usage is allowed with a commercial licence — please contact the author. If you want to pay for new functionality, please feel free to get in touch.

---

## Rule engine examples

- Taking 1/15 of an equity fund each year and adding it to income for spending
- Taking 50% of the positive cashflow of one asset to make an extra mortgage downpayment
- Taking 100% of the sales value of a stock/company and transferring it to an equity fund on exit
- Transferring value from one asset to another (to simulate if it yields more wealth)
- A child asset has both income and expenses until the child moves out, then is removed from your economy
- Public pension is added to income the year you retire

Asset configurations can be run with different prognoses: `realistic`, `positive`, `negative`, `tenpercent`, `zero`, `variable`.
Each asset can have a change rate that differs per year. Assets can both increase and decrease in value.

#### Change rate examples

- Interest: 4% in 2023 → 6% in 2024 → 5% in 2025
- Car or boat: −10% per year (depreciation)

The system sums everything up and shows you all the details on how your economy behaves.

It outputs a very detailed excel spreadsheet of your future economy prognosis where you can see how well a single asset performs, or you can look at the total performance for your private or company economy - or the sum of your private and company economy. Se examples and definitions below.
It also outputs a page with the spread of your different asset types - so you can see where you are most heavily invested.

> **Note:** A `transfer` must always target an asset later in the config file (assets are calculated in order). A `source` must always retrieve from an asset earlier in the config file.

#### Special reserved asset names

| Name | Description |
|---|---|
| `total` | Sum of all assets |
| `company` | Total company summary |
| `private` | Total private summary |
| `income` | Collects all taxed private income (not the same as salary) |

---

## Dashboards

### Actual assets dashboard (`/admin`)
- **Asset Overview (GUI)** — App\Filament\Widgets\AssetOverviewWidget
  - Hva: Hurtigstatus for totaler
  - Matematikk: Henter summer fra FireCalculationService. Viser bl.a. Total Assets (sum markedsverdi), Investment (liquid) Assets, Net Worth = Total Assets − Total Liabilities, Total Mortgage = sum lån.
- **Monthly Cashflow (GUI)** — App\Filament\Widgets\MonthlyCashflowWidget
  - Hva: Månedlig inntekt/utgift, netto cashflow og forbruksgrad
  - Matematikk: Monthly Cashflow = monthlyIncome − monthlyExpenses. Expense Ratio = annualExpenses / annualIncome × 100. Viser også årsverdier.
- **FIRE Progress & Crossover Point** — App\Filament\Widgets\FireProgressAndCrossover
  - Hva: Linjegraf som viser portefølje vs FIRE‑tall og inntekt/utgift (kun inneværende år)
  - Matematikk: FIRE‑tall = 25 × årlige utgifter. Potensiell årlig inntekt = porteføljeverdi × 4%.
- **FIRE: Crossover Point** — App\Filament\Widgets\FireCrossoverWidget
  - Hva: Om passiv inntekt overstiger utgifter (oppnådd/ikke)
  - Matematikk: Basert på FireCalculationService->crossoverAchieved (passiv inntekt > utgifter).
- **FIRE Progress Over Time** — App\Filament\Widgets\FireMetricsOverview
  - Hva: Linjegraf med estimert nettoformue vs FIRE‑tall over 30 år
  - Matematikk: Nettoformue projiseres med årlig vekst 7%: (forrige + årlig sparing) × 1.07. FIRE‑tall inflasjonsjusteres ~3% p.a. (25 × årlige utgifter × 1.03^n).
- **Net Worth Over Time** — App\Filament\Widgets\NetWorthOverTime
  - Hva: Historisk nettoformue per år (kun til og med inneværende år)
  - Matematikk: Nettoformue(år) = sum(asset_market_amount) − sum(mortgage_amount) fra AssetYear pr år.
- **Cash Flow Over Time** — App\Filament\Widgets\CashFlowOverTime
  - Hva: Linjegraf for årlig inntekt, utgifter og netto cashflow
  - Matematikk: Årlig inntekt = sum(income_amount × faktor) der faktor = 12 ved monthly, ellers 1. Årlige utgifter tilsvarende med expence_amount. Net Cashflow = inntekt − utgifter.
- **Asset Allocation by Type** — App\Filament\Widgets\AssetAllocationByType
  - Hva: Fordeling etter asset‑type for inneværende år
  - Matematikk: Grupperer AssetYear etter asset.asset_type, summerer asset_market_amount (> 0).
- **Asset Allocation by Tax Type** — App\Filament\Widgets\AssetAllocationByTaxType
  - Hva: Fordeling etter skattekategori for inneværende år
  - Matematikk: Grupperer AssetYear etter asset.tax_type, summerer asset_market_amount (> 0).
- **Asset Allocation by Category** — App\Filament\Widgets\AssetAllocationByCategory
  - Hva: Fordeling etter kategori (fra asset type relasjon) for inneværende år
  - Matematikk: Grupperer AssetYear etter asset.assetType.category, summerer asset_market_amount (> 0).
- **Actual Tax Rate Over Time** — App\Filament\Widgets\ActualTaxRateOverTime
  - Hva: Faktisk/estimert skattesats over tid
  - Matematikk: Årsinntekt = sum(income_amount × faktor). Årsskatt = sum(tax_amount) eller estimert norsk modell. Skattesats = skatt/inntekt × 100. Viser også enkel marginalskatt.
- **Retirement Readiness** — App\Filament\Widgets\RetirementReadinessChart
  - Hva: Hvor nær pensjonsmål du er, med kapitalbehov og pensjonsekvivalenter
  - Matematikk: Nettoformue projiseres med ~6% p.a.; etter pensjon: årlig uttak 4%. Kapitalbehov ≈ 25 × 80% av nåværende utgifter. Pensjonsekvivalent beregnes forenklet (grunnpensjon + OTP 4%‑uttak).
- **Monthly Expense Breakdown** — App\Filament\Widgets\ExpenseBreakdownChart
  - Hva: Doughnut‑diagram av månedlige utgifter pr type
  - Matematikk: Sum expence_amount per asset‑type for inneværende år, delt på 12.

### Simulation dashboard (`/admin/config/{configuration}/sim/{simulation}/dashboard`)
- **Simulation Overview** — App\Filament\Widgets\SimulationStatsOverviewWidget
  - Hva: Nøkkeltall for porteføljen i simuleringen
  - Matematikk: Startverdi = sum første års start_value. Sluttverdi = sum siste års end_value. Total vekst = slutt − start. CAGR ≈ (slutt/start)^(1/år) − 1. Viser også totale inntekter, utgifter, netto og skatt.
- **Simulation FIRE Analysis** — App\Filament\Widgets\SimulationFireAnalysisWidget
  - Hva: FIRE‑tall, fremdrift, antatt år til FIRE og dekning av utgifter
  - Matematikk: Årlige utgifter = gjennomsnitt av expence_amount. FIRE‑tall = 25 × årlige utgifter. Nåverdi = sum start_value. Fremdrift = nåverdi/FIRE‑tall × 100. År til FIRE (forenklet). Passiv inntekt = 4% av portefølje.
- **Simulation Tax Analysis** — App\Filament\Widgets\SimulationTaxAnalysisWidget
  - Hva: Skatteanalyse i simuleringen
  - Matematikk: Total skatt = sum asset_tax_amount. Effektiv skattesats = total skatt / total inntekt × 100. Skatt på gevinster = skatt / samlede gevinster × 100. Viser også høyeste/laveste skatteår (beløp).
- **Portfolio Allocation Evolution** — App\Filament\Widgets\SimulationAssetAllocationChartWidget
  - Hva: Fordeling av portefølje i siste simulerte år
  - Matematikk: Finn siste år i datasettet, grupper end_value per asset_type og summer.

---

## Configuration reference

### Supported features

- Percentage change rates on expenses, income, and asset values
- Mortgage calculations, including new mortgages replacing previous ones
- Incremental decrease of income / expense / asset (negative amount)
- Norwegian fortune, property, income, capital gains, salary tax calculations
- Asset grouping into `total`, `company`, and `private` summaries
- Max loan capacity estimation

### Rule engine syntax
- +10% - Adds 10% to amount
- -10% - Subtracts 10% from amount
- 10% - Calculates 10% of the amount
- +1000 - Adds 1000 to amount
- -1000 - Subtracts 1000 from amount
- +1/10 - Adds 1 tenth of the amount yearly
- -1/10 - Subtracts 1 tenth of the amount yearly
- 1/10 - Calculates 1/10 of the amount. Does not change the amount
- +1|10 - Adds 1 tenth of the amount yearly, and subtracts nevner with one(so next value is 1/9, then 1/8, 1/7 etc)
- -1|10 - Subtracts 1 tenth of the amount yearly. Then subtracts nevner with one. (so next value is 1/9, then 1/8, 1/7 etc). Perfect for usage to i.e empty an asset over 10 years.
- 1|10 - Calculates 1|10 of the amount. Does not change the amount.

**source**

Default den asset man står på, medmindre annet er spesifisert. Brukes for å beregne beløpet basert på verdiene i en annen asset enn den man står på. Will not reduce the value of the source asset,

**transfer**

Overføring av beløp fra den asset regelen er på til den asset som er spesifisert i regelen,
Beløp blir kun overført hvis det er spesifisert en transfer på asset som skal sende beløpet, hvis ikke blir beløpet lagt til den asset man står på.
Transfer kan kun foregå til tidligere prosesserte assets i rekkefølgen om det er extraDownPayment på lån, ellers så må transfer alltid skje til en kommende asset.

---

## Asset types

Legend: 🟢 = Liquid, 🔴 = Non-liquid

| Type | Visningsnavn | Liquid | Beskrivelse |
|---|---|---|---|
| equityfund | Aksjefond | 🟢 | Aksjefond (aksjefond/fond). |
| bondfund | Rentefond | 🟢 | Fond som investerer primært i obligasjoner. |
| mixedfund | Kombinasjonsfond | 🟢 | Balanserte fond med både aksjer og obligasjoner. |
| indexfund | Indeksfond | 🟢 | Passivt fond som følger markedsindekser. |
| hedgefund | Hedgefond | 🟢 | Alternative fond med fleksible strategier. |
| stock | Aksjer | 🟢 | Hensyntar fritaksregelen; selskap uten skatt ved salg, privatperson beskattes ved salg. |
| ask | Aksjesparekonto (ASK) | 🟢 | Skattefavorisert aksjesparing. |
| bonds | Obligasjoner | 🟢 | Stats- og selskapsobligasjoner. |
| options | Opsjoner | 🟢 | Finansielle derivater (rett, ikke plikt, til å kjøpe/selge). |
| warrants | Warranter | 🟢 | Langsiktige opsjoner utstedt av selskap. |
| bank | Bankkonto | 🟢 | Ordinær innskuddskonto. |
| cash | Kontanter | 🟢 | Fysisk kontanter / umiddelbar likviditet. |
| savings | Sparekonto | 🟢 | Høyrentekonto. |
| timedeposit | Tidsinnskudd | 🟢 | Bundet innskudd med garantert rente. |
| moneymarket | Pengemarkedsfond | 🟢 | Kortsiktige rentefond. |
| car | Bil | 🔴 | Personlig kjøretøy. |
| boat | Båt | 🔴 | Fritidsbåt/vannfartøy. |
| jewelry | Smykker | 🔴 | Smykker og verdigjenstander. |
| furniture | Møbler | 🔴 | Innbo og løsøre. |
| crypto | Krypto | 🟢 | Digitale valutaer/kryptoaktiva. |
| gold | Gull | 🟢 | Fysisk gull og edelmetaller. |
| ips | Pensjonssparing (IPS) | 🟢 | Pensjonssparing med spesielle skatteregler. |
| endowment | Kapitalforsikring | 🔴 | Skatteeffektiv sparing i forsikring. |
| house | Bolig | 🔴 | Primær- eller sekundærbolig. |
| rental | Utleieeiendom | 🔴 | Eiendom for utleie og inntekt. |
| cabin | Hytte | 🔴 | Fritidsbolig. |
| salary | Lønn | 🔴 | Lønnsinntekt fra arbeidsgiver. |
| income | Annen inntekt | 🔴 | Diverse inntektskilder. |
| pension | Pensjon | 🔴 | Offentlig pensjon/pensjonsutbetalinger. |
| otp | Tjenestepensjon (OTP) | 🔴 | Arbeidsgiverpensjon (obligatorisk tjenestepensjon). |
| child | Barnetrygd | 🔴 | Barnetrygd og andre familieytelser. |
| inheritance | Arv | 🔴 | Arv og gaver. |
| company | Selskap | 🔴 | Eierandel i selskap/bedrift. |
| iphone | iPhone | 🔴 | Teknologiprodukter (eksempel/testkategori). |
| applestock | Apple-aksjer | 🟢 | Aksjer i Apple Inc. (eksempel på enkeltselskap). |
| test | Test | 🔴 | Testtype for utvikling/validering. |
| kpi | KPI | 🔴 | Konsumprisindeks (referanse/indikator). |
| spouse | Ektefelle | 🔴 | Ektefelles inntekter og utgifter. |

---

## Roadmap

### Priority
- Check calculations for property tax
- Check calculations for bracket tax
- Support for personfradrag in tax calculations - calculating a bit too high now
- Support for reading tax configurations pr year and country (support for more than norwegian tax regime). Only using the current years tax regime for all calculations now
- Support for yearly tax on interest on money in the bank.
- Support for two different types of stock - liquid and non-liquid (greymarket). Liquid stocks will be sold until zero when you retire. Non-liquid stocks will be kept until you die.
- Fortune tax is divided into state and municipality tax. Should be calculated separately.
- Correct the FIRE calulations - the correct is 4% the first year and then KPI adjusted the following years.

### Future ideas
- Support for factor on a rule like +1000
- Support for changerates on rules, like adding 5% to the +1000 rule each year.
-  Company fortune should be retrieved from the previous year not the current year (tax vise)
- //https://www.skatteetaten.no/person/skatt/hjelp-til-riktig-skatt/verdsettingsrabatt-ved-fastsetting-av-formue/
- Calculate only 1 year of a mortgage at a time, to avoid this vertical processing problem.
- All transfers to next year? (to avoid vertical processing problem)
- Extra nedbetaling av lån skaper masse utfordringer (fordi det påvirker mange verdier som allerede er beregnet og må reberegnes)
- Catch 22. If calculating otp from salary we can not transfer to salary from otp because of sequenze problems. Have to add a "income" type at the end of the config to add all such transfers, to split between salary and income (from investements)
- Når man betaler ned et lån og det blir penger igjen etter extraDownpayment så repeteres ikke det gjenværende beløpet på asset'en den kom fra. Både riktig og galt når repeat er false.... Men reglene skal ikke repeteres (eller må vi ha separat repeat på ulike deler)
- Property tax should use the tax value of year-2 (Holmestrand at least))
- Company fortune tax for private person should use the tax value of year-2
- rename group => configuration [private|company]
- Support for selling parts of partly liquid assets every year to get the cashflow to zero. (has top calculate reversed tax - the amount you neet to pay + tax has to be transfered to cashflow)
- Tax configuration pr year and countries (support for more than norwegian tax regime). Only using the current years tax regime for all calculations now
- Take into account the number of years you have owned an asset regardign tax calculation on i.e house and cabin.
- Gjøre beregningene pr år så asset, ikke asset pr år som nå (da vil ikke verdiøkning o.l være med) (BIG REFACTORING - but cod is prepared for it)-
- Klassifisere F.I.R.E oppnåelse pr år
- Showing all values compared to KPI index (relative value) and how we perform compared to kpi
- Refactoring and cleanup of code
- F.I.R.E - Use up 4% of partly liquid assets from wishPensionYear to DeathYear to see how it handles. Not needed anymore since using up a divisor of your assets (1/10) until you die is a better way to use up liquid assets.
- Retrieving asset values from API, like Crypto/Fond/stocks

## Data Structure Reference

### 📋 Input Configuration Structure

The following tables describe the JSON configuration structure for asset configurations.

#### Configuration Meta (Top Level)

| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| `meta.name` | string | ✅ | Your name or alias | "John Doe" |
| `meta.description` | string | ❌ | Longer description | "My financial plan" |
| `meta.birthYear` | integer | ✅ | Year you were born | 1985 |
| `meta.prognoseAge` | integer | ❌ | Age for projection focus | 50 |
| `meta.pensionOfficialAge` | integer | ❌ | Official retirement age | 67 |
| `meta.pensionWishAge` | integer | ❌ | Desired retirement age | 63 |
| `meta.deathAge` | integer | ✅ | Expected age of death | 82 |
| `meta.exportStartAge` | integer | ❌ | Excel export start year | 2023 |
| `meta.icon` | string | ❌ | Heroicon name | "heroicon-o-user" |
| `meta.color` | string | ❌ | Color hint for UI | "#f97316" |
| `meta.tags` | array | ❌ | Tags for grouping | ["advanced", "test"] |
| `meta.country` | string | ❌ | 2-letter country code | "no" |

#### Asset Meta (Asset Level)

| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| `meta.type` | string | ✅ | Asset type code | "salary", "house", "equityfund" |
| `meta.group` | string | ✅ | Owner group | "private" or "company" |
| `meta.name` | string | ✅ | Short display name | "My House" |
| `meta.description` | string | ❌ | Detailed description | "Primary residence" |
| `meta.active` | boolean | ❌ | Include in calculations | true (default) |
| `meta.taxProperty` | string | ❌ | Property tax code | "property" |
| `meta.debug` | boolean | ❌ | Include in debug exports | false (default) |
| `meta.icon` | string | ❌ | Heroicon name | "heroicon-o-home" |
| `meta.color` | string | ❌ | Color hint | "#10b981" |
| `meta.tags` | array | ❌ | Tags for grouping | ["real-estate"] |
| `meta.country` | string | ❌ | 2-letter country code | "no" |

#### Income Configuration

| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| `income.amount` | decimal | ❌ | Amount before tax | 50000 |
| `income.factor` | enum | ❌ | Frequency multiplier | "monthly" or "yearly" |
| `income.changerate` | string | ❌ | Change rate reference | "changerates.kpi" |
| `income.rule` | string | ❌ | Calculation rule | "+1000", "5%", "1/10" |
| `income.transfer` | string | ❌ | Transfer to asset | "otp.$year.asset.amount" |
| `income.source` | string | ❌ | Source from asset | "salary.$year.income.amount" |
| `income.repeat` | boolean | ❌ | Repeat for future years | true |
| `income.name` | string | ❌ | Income name | "Salary" |

#### Expense Configuration

| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| `expence.amount` | decimal | ❌ | Amount before tax | 15000 |
| `expence.factor` | enum | ❌ | Frequency multiplier | "monthly" or "yearly" |
| `expence.changerate` | string | ❌ | Change rate reference | "changerates.kpi" |
| `expence.rule` | string | ❌ | Calculation rule | "+500", "-10%" |
| `expence.transfer` | string | ❌ | Transfer to asset | "house.$year.mortgage.amount" |
| `expence.source` | string | ❌ | Source from asset | "rental.$year.expence.amount" |
| `expence.repeat` | boolean | ❌ | Repeat for future years | true |
| `expence.name` | string | ❌ | Expense name | "Living costs" |

#### Asset Configuration

| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| `asset.marketAmount` | decimal | ✅ | Market value | 3000000 |
| `asset.acquisitionAmount` | decimal | ❌ | Acquisition cost | 2500000 |
| `asset.equityAmount` | decimal | ❌ | Equity amount | 1500000 |
| `asset.paidAmount` | decimal | ❌ | Amount paid | 2500000 |
| `asset.taxableInitialAmount` | decimal | ❌ | Taxable value | 2800000 |
| `asset.changerate` | string | ❌ | Change rate reference | "changerates.house" |
| `asset.rule` | string | ❌ | Calculation rule | "+6000", "1/15" |
| `asset.transfer` | string | ❌ | Transfer to asset | "income.$year.income.amount" |
| `asset.source` | string | ❌ | Source from asset | "salary.$year.income.amount" |
| `asset.repeat` | boolean | ❌ | Repeat for future years | true |
| `asset.name` | string | ❌ | Asset name | "Primary residence" |

#### Mortgage Configuration

| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| `mortgage.amount` | decimal | ✅ | Original loan amount | 1500000 |
| `mortgage.interest` | string/decimal | ✅ | Interest rate % or reference | "changerates.interest" or 5.5 |
| `mortgage.years` | integer | ✅ | Loan duration in years | 25 |
| `mortgage.interestOnlyYears` | integer | ❌ | Interest-only period | 5 |
| `mortgage.gebyr` | decimal | ❌ | Annual fee | 600 |
| `mortgage.extraDownpaymentAmount` | string/decimal | ❌ | Extra annual payment | 50000 |
| `mortgage.tax` | decimal | ❌ | Tax deduction % | 22 |

---

### 📊 Output Data Structure

The following tables describe the calculated output structure for each asset per year.

#### Income Output

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `income.amount` | decimal | Income before tax | From config + changerate |
| `income.changerate` | string | Change rate reference | From config |
| `income.changeratePercent` | decimal | Change rate as % | Resolved from changerate |
| `income.rule` | string | Applied rule | From config |
| `income.transfer` | string | Transfer destination | From config |
| `income.repeat` | boolean | Repeat flag | From config |
| `income.transferedAmount` | decimal | Transferred amount | From transfer/source/rule |

#### Expense Output

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `expence.amount` | decimal | Expense before tax | From config + changerate |
| `expence.changerate` | string | Change rate reference | From config |
| `expence.changeratePercent` | decimal | Change rate as % | Resolved from changerate |
| `expence.rule` | string | Applied rule | From config |
| `expence.transfer` | string | Transfer destination | From config |
| `expence.repeat` | boolean | Repeat flag | From config |
| `expence.transferedAmount` | decimal | Transferred amount | From transfer/source/rule |

#### Cashflow Output

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `cashflow.beforeTaxAmount` | decimal | Cashflow before tax | income + income.transfered - expence - mortgage.term |
| `cashflow.afterTaxAmount` | decimal | Cashflow after tax | beforeTax - tax - fortuneTax - propertyTax |
| `cashflow.beforeTaxAggregatedAmount` | decimal | Cumulative before tax | Sum from start year |
| `cashflow.afterTaxAggregatedAmount` | decimal | Cumulative after tax | Sum from start year |
| `cashflow.taxAmount` | decimal | Tax amount | From tax calculation |
| `cashflow.taxRate` | decimal | Tax rate (decimal) | Tax / income |
| `cashflow.transferedAmount` | decimal | Transferred amount | From transfer/source/rule |
| `cashflow.rule` | string | Applied rule | From config |
| `cashflow.transfer` | string | Transfer destination | From config |
| `cashflow.repeat` | boolean | Repeat flag | From config |
| `cashflow.description` | string | Description | Generated description |

#### Mortgage Output

| Field                             | Type | Description | Calculation |
|-----------------------------------|------|-------------|-------------|
| `mortgage.amount`                 | decimal | Original loan amount | From config (constant) |
| `mortgage.termAmount`             | decimal | Total annual payment | interest + principal + gebyr |
| `mortgage.interestAmount`         | decimal | Interest payment | balance × interest rate |
| `mortgage.principalAmount`        | decimal | Principal payment | Amortization amount |
| `mortgage.balanceAmount`          | decimal | Remaining balance | Previous balance - principal |
| `mortgage.extraDownpaymentAmount` | decimal | Extra payment | From config/transfer |
| `mortgage.transferedAmount`       | decimal | Transferred amount | From transfer/source/rule |
| `mortgage.interestPercent`        | decimal | Interest rate % | From config/changerate (e.g., 5.5 for 5.5%) |
| `mortgage.interestRate`           | decimal | Interest rate (decimal) | interestPercent / 100 (e.g., 0.055) |
| `mortgage.years`                  | integer | Remaining years | Decreases annually |
| `mortgage.gebyrAmount`            | decimal | Annual fee | From config |
| `mortgage.taxDeductableAmount`    | decimal | Tax deduction amount | interest × tax rate |
| `mortgage.taxDeductablePercent`   | decimal | Tax deduction % | From config (e.g., 22 for 22%) |
| `mortgage.taxDeductableRate`      | decimal | Tax deduction rate (decimal) | taxDeductablePercent / 100 (e.g., 0.22) |
| `mortgage.description`            | string | Description | Generated description |

#### Asset Output

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `asset.marketAmount` | decimal | Market value | From config + changerate + rule |
| `asset.marketMortgageDeductedAmount` | decimal | Value minus loan | marketAmount - mortgage.balance |
| `asset.acquisitionAmount` | decimal | Acquisition cost | From config (adjusted) |
| `asset.acquisitionInitialAmount` | decimal | Initial acquisition | Set first time only |
| `asset.equityAmount` | decimal | Equity | acquisition - mortgage.balance |
| `asset.equityInitialAmount` | decimal | Initial equity | Set first time only |
| `asset.paidAmount` | decimal | Total paid | Includes interest, principal, fees |
| `asset.paidInitialAmount` | decimal | Initial paid | Set first time only |
| `asset.transferedAmount` | decimal | Transferred amount | From transfer/source/rule |
| `asset.mortageRate` | decimal | Loan-to-value ratio | mortgage.balance / marketAmount |
| `asset.taxableRate` | decimal | Taxable % | taxableAmount / marketAmount |
| `asset.taxableAmount` | decimal | Taxable value minus loan | taxableInitial - mortgage.balance |
| `asset.taxableInitialAmount` | decimal | Taxable value | From config (adjusted) |
| `asset.taxableAmountOverride` | boolean | Override flag | Auto-set if configured |
| `asset.taxFortuneRate` | decimal | Fortune tax rate | From tax config |
| `asset.taxFortuneAmount` | decimal | Fortune tax | taxableAmount × tax rate |
| `asset.taxablePropertyRate` | decimal | Property tax rate | From tax config |
| `asset.taxablePropertyAmount` | decimal | Property taxable amount | From tax calculation |
| `asset.taxPropertyAmount` | decimal | Property tax | From tax calculation |
| `asset.taxPropertyRate` | decimal | Property tax rate | From tax config |
| `asset.changerate` | string | Change rate reference | From config |
| `asset.rule` | string | Applied rule | From config |
| `asset.transfer` | string | Transfer destination | From config |
| `asset.repeat` | boolean | Repeat flag | From config |
| `asset.description` | string | Description | Generated description |

#### Realization Output (Sale Simulation)

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `realization.amount` | decimal | Net after sale | marketAmount - realization.taxAmount |
| `realization.taxableAmount` | decimal | Taxable gain | marketAmount - acquisitionAmount |
| `realization.taxAmount` | decimal | Tax on sale | taxableAmount × taxRate - taxShield |
| `realization.taxRate` | decimal | Tax rate on gain | From tax config |
| `realization.taxShieldAmount` | decimal | Tax shield amount | Accumulated/used shield |
| `realization.taxShieldRate` | decimal | Tax shield rate | From tax config |
| `realization.description` | string | Description | Generated description |

#### Yield Output
If asset.acquisitionAmount is empty, we use asset.marketAmount instead.
| Field             | Type | Description   | Calculation                                                                                                                                                                                   |
|-------------------|------|---------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `yield.grossRate` | decimal | Gross yield % | (income.amount / asset.acquisitionAmount) × 100                                                                                                                                               |
| `yield.netRate`   | decimal | Net yield %   | ((income.amount - expence.amount - asset.propertyTaxAmount - mortgage.termAmount) / asset.acquisitionAmount) × 100   - calculated with fiancing cost. FortunTax and IncomeTax is not included |
| `yield.capRate`   | decimal | Cap yield %     | ((income.amount - expence.amount - asset.propertyTaxAmount) / asset.acquisitionAmount) × 100 - calculated without looking at financing cost. FortunTax and IncomeTax is not included                                                 |

#### Financial Metrics Output

Comprehensive financial analysis metrics calculated for each asset and year.

##### Investment Returns

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `metrics.noi` | decimal | Net Operating Income | income.amount - expence.amount - asset.propertyTaxAmount |
| `metrics.roiRate` | decimal | Return on Investment (rate) | ((asset.marketAmount - asset.acquisitionAmount) + cashflow.afterTaxAmount) / asset.acquisitionAmount |
| `metrics.roiPercent` | decimal | Return on Investment (%) | metrics.roiRate × 100 |
| `metrics.totalReturnAmount` | decimal | Total Return (amount) | (asset.marketAmount - asset.acquisitionAmount) + cashflow.afterTaxAmount |
| `metrics.totalReturnRate` | decimal | Total Return (rate) | metrics.totalReturnAmount / asset.acquisitionAmount |
| `metrics.totalReturnPercent` | decimal | Total Return (%) | metrics.totalReturnRate × 100 |
| `metrics.cocRate` | decimal | Cash-on-Cash Return (rate) | cashflow.afterTaxAmount / asset.paidAmount |
| `metrics.cocPercent` | decimal | Cash-on-Cash Return (%) | metrics.cocRate × 100 |

##### Property Metrics

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `metrics.grm` | decimal | Gross Rent Multiplier | asset.marketAmount / income.amount |

##### Leverage Metrics

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `metrics.dscr` | decimal | Debt Service Coverage Ratio | metrics.noi / mortgage.termAmount |
| `metrics.ltvRate` | decimal | Loan-to-Value Ratio (rate) | mortgage.balanceAmount / asset.marketAmount |
| `metrics.ltvPercent` | decimal | Loan-to-Value Ratio (%) | metrics.ltvRate × 100 |
| `metrics.deRatio` | decimal | Debt-to-Equity Ratio | mortgage.balanceAmount / asset.equityAmount |

##### Profitability Ratios

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `metrics.roeRate` | decimal | Return on Equity (rate) | cashflow.afterTaxAmount / asset.equityAmount |
| `metrics.roePercent` | decimal | Return on Equity (%) | metrics.roeRate × 100 |
| `metrics.roaRate` | decimal | Return on Assets (rate) | cashflow.afterTaxAmount / asset.marketAmount |
| `metrics.roaPercent` | decimal | Return on Assets (%) | metrics.roaRate × 100 |

##### Valuation Metrics

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `metrics.pbRatio` | decimal | Price-to-Book Ratio | asset.marketAmount / asset.equityAmount |
| `metrics.evEbitda` | decimal | Enterprise Value/EBITDA | (asset.marketAmount + mortgage.balanceAmount) / metrics.noi |

##### Liquidity Metrics

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `metrics.currentRatio` | decimal | Current Ratio | (abs(cashflow.afterTaxAmount) + asset.equityAmount) / mortgage.termAmount |

#### Potential Output (Bank Perspective)

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `potential.incomeAmount` | decimal | Potential income | For salary/pension types |
| `potential.mortgageAmount` | decimal | Max loan capacity | income × 5 (Norwegian rule) |

#### FIRE Output (Financial Independence)

| Field | Type | Description | Calculation |
|-------|------|-------------|-------------|
| `fire.incomeAmount` | decimal | FIRE income | 4% of liquid assets |
| `fire.expenceAmount` | decimal | FIRE expenses | Actual expenses |
| `fire.cashFlowAmount` | decimal | FIRE cashflow | incomeAmount - expenceAmount |
| `fire.savingAmount` | decimal | FIRE savings | After-tax cashflow (if is_saving) |
| `fire.rate` | decimal | FIRE ratio | incomeAmount / expenceAmount |
| `fire.percent` | decimal | FIRE percentage | rate × 100 |
| `fire.savingRate` | decimal | Savings rate | savingAmount / incomeAmount |

---

## Example config

### Example simple config

```json
{
"meta": {
"name": "John Doe",
"birthYear": "1975",
"prognoseYear": "50",
"pensionOfficialYear": "67",
"pensionWishYear": "63",
"deathYear": "82"
},
"income": {
"meta": {
"type": "income",
"group": "private",
"name": "Income",
"description": "Income",
"active": true,
"tax": "salary"
},
"2023": {
"description": "Income: Income | Expense: ",
"income": {
"name": "Income",
"description": "Income",
"amount": 40000,
"factor": 12,
"changerate": "changerates.kpi",
"repeat": true
},
"expence": {
"name": "Expences",
"description": "",
"amount": 15000,
"factor": 12,
"changerate": "changerates.kpi",
"repeat": true
}
},
"$pensionWishYear": {
"description": "Income: Pensioned, no more income from here",
"income": {
"name": "Income",
"description": "Pensioned, no more income from here",
"amount": 0,
"changerate": "changerates.zero",
"repeat": false
}
}
},
"inheritance": {
"meta": {
"type": "inheritance",
"group": "private",
"name": "inheritance",
"description": "inheritance",
"active": true,
"tax": "inheritance"
},
"2037": {
"description": "Asset: ",
"asset": {
"name": "inheritance",
"description": "",
"marketAmount": 1000000,
"acquisitionAmount" : 1,
"paidAmount": 1,
"changerate": "changerates.kpi",
"repeat": true
}
}
},
"pension": {
"meta": {
"type": "pension",
"group": "private",
"name": "Folketrygden",
"description": "Folketrygden",
"active": true,
"tax": "pension"
},
"$pensionOfficialYear": {
"description": "Income: Folketrygden fra $pensionOfficialYear",
"income": {
"name": "Folketrygden",
"description": "Folketrygden fra $pensionOfficialYear",
"amount": 15000,
"factor": 12,
"changerate": "changerates.kpi",
"repeat": true
}
}
},
"otp": {
"meta" : {
"type": "equityfund",
"group": "private",
"name": "OTP",
"description": "OTP",
"active": true,
"tax": "equityfund"
},
"2023": {
"description": "Asset: OTP Sparing frem til pensjonsår",
"asset": {
"amount": 500000,
"rule": "5%",
"source": "income.$year.income.amount",
"changerate": "changerates.otp",
"description": "OTP Sparing frem til pensjonsår",
"repeat": true
}
},
"$otpStartYear": {
"asset": {
"rule": "1|$otpYears",
"transfer": "income.$year.income.amount",
"source": "",
"changerate": "changerates.otp",
"description": "OTP fra $otpStartYear, 1|$otpYears av formuen",
"repeat": true
}
}
},
"house": {
"meta" : {
"type": "house",
"group": "private",
"name": "My house",
"description": "Here I live",
"active" : true,
"tax": "house",
"taxProperty": "property"
},
"2023": {
"asset": {
"marketAmount": "3000000",
"changerate": "changerates.house",
"description": "",
"repeat": true
},
"expence": {
"name": "Utgifter",
"description": "Kommunale/Forsikring/Strøm/Eiendomsskatt 7300 mnd",
"amount": 7300,
"factor": 12,
"changerate": "changerates.kpi",
"repeat": true
},
"mortgage": {
"amount": 1500000,
"interest": "changerates.interest",
"gebyr": 600,
"tax": 22,
"paymentExtra": "home.$year.cashflow.amount",
"years": 20
}
}
},
"fond": {
"meta" : {
"type": "equityfund",
"group": "private",
"name": "fond privat",
"description": "",
"active": true,
"tax": "equityfund"
},
"2022": {
"asset": {
"amount": 2000000,
"rule": "+6000",
"changerate": "changerates.equityfund",
"description": "Første innskudd på 2 millioner",
"repeat": true
}
},
"2033": {
"asset": {
"name": "Monthly savings",
"description": "Slutter å sette inn 6000,- pr år",
"rule": "",
"repeat": true
}
},
"$pensionWishYear": {
"asset": {
"rule": "1|$pensionWishYears",
"transfer": "income.$year.income.amount",
"changerate": "changerates.equityfund",
"description": "Uttak fra $pensionWishYear, -1/$pensionWishYears",
"repeat": true
}
}
},
"cash": {
"meta" : {
"type": "cash",
"group": "private",
"name": "Cash",
"description": "",
"active": true,
"tax": "cash"
},
"2022": {
"asset": {
"marketAmount": 50000,
"changerate": "changerates.cash",
"description": "Kontanter p.t.",
"repeat": true
}
},
"$pensionWishYear": {
"asset": {
"rule": "1|$pensionWishYears",
"transfer": "income.$year.income.amount",
"changerate": "changerates.equityfund",
"description": "Uttak fra $pensionWishYear, 1|$pensionWishYears",
"repeat": true
}
}
},
"car": {
"meta" : {
"type": "car",
"group": "private",
"name": "Avensis",
"description": "",
"active": true,
"tax": "car"
},
"2020": {
"asset": {
"marketAmount": 50000,
"changerate": "changerates.car",
"description": "verditap",
"repeat": true
},
"expence": {
"name": "Utgifter",
"description": "Drivstoff/Forsikring/Vedlikehold 4000,- pr mnd (med høy dieselpris)",
"amount": 3000,
"factor": 12,
"changerate": "changerates.kpi",
"repeat": true
}
}
}
}

Integrated AI chatbot

2. Comprehensive Financial Assistant Capabilities
   Configuration Management:
   ✅ Create new financial configurations with required fields (name, birth_year, death_age, pension_wish_age, risk_tolerance)
   ✅ Intelligent data extraction from natural language input
   ✅ Step-by-step guidance for missing information
   Asset Management:
   ✅ Add various asset types (house, car, boat, cabin, funds, crypto, investments)
   ✅ Extract market values and mortgage information
   ✅ Create asset years with current year data
   ✅ Automatic asset type creation and categorization
   Income Tracking:
   ✅ Add income sources (salary, pension, benefits, barnetrygd)
   ✅ Support for monthly/yearly frequency conversion
   ✅ Automatic income asset creation
   Life Event Planning:
   ✅ Retirement planning with salary cessation and pension income
   ✅ Children planning with barnetrygd and expense tracking
   ✅ Inheritance event planning
   ✅ Property change planning (buying/selling houses)
3. Advanced Financial Planning Logic
   Retirement Planning:
   ✅ Automatic salary income cessation at retirement age
   ✅ Pension income creation from retirement to death
   ✅ OTP withdrawal planning over 15 years
   ✅ Integration with existing prognosis logic
   Children Financial Planning:
   ✅ Barnetrygd income until age 18
   ✅ Child expense tracking until leaving home
   ✅ Configurable leaving home age and monthly expenses
   Life Events:
   ✅ Inheritance planning with timing and amounts
   ✅ Property change events (selling current, buying new)
   ✅ Integration with existing asset management
4. Smart Conversation Management
   ✅ Intent analysis for natural language understanding
   ✅ Context-aware responses based on current configuration
   ✅ Error handling with graceful fallbacks
   ✅ Help system with comprehensive feature overview
6. Security & Data Integrity
   ✅ User authentication and authorization
   ✅ Team-based data isolation
   ✅ Input validation and sanitization
   ✅ Transaction-based database operations
   🧪 Quality Assurance:
   ✅ 14 comprehensive tests covering all major functionality
   ✅ Error handling with graceful degradation
   ✅ Code formatting with Laravel Pint compliance
   ✅ Service architecture with proper separation of concerns
   🎯 User Experience Features:
   ✅ Natural Language Processing: Users can type requests in plain English
   ✅ Progressive Data Collection: Asks for missing information step-by-step
   ✅ Visual Feedback: Loading states, timestamps, and conversation history
   ✅ Configuration Context: Remembers current working configuration
   ✅ Help System: Comprehensive guidance on available features
   📱 Technical Implementation:
   ✅ Livewire 3 Component: Real-time interactivity without page refreshes
   ✅ Filament 4 Integration: Seamlessly integrated into admin panel
   ✅ Service Layer Architecture: Clean separation between UI and business logic
   ✅ Render Hook Integration: Appears on all pages automatically

## 🤖 AI Financial Assistant

The Wealth Prognosis application includes a powerful AI assistant that helps you manage your financial data using natural language. The assistant is available throughout the application and can understand both English and Norwegian commands.

### 🎯 **Core Features**

**Asset Management**
- ✅ Create new assets with market values and loan information
- ✅ Update existing asset values and mortgage details
- ✅ Support for all asset types (house, car, boat, investments, etc.)
- ✅ Automatic asset categorization and type detection

**Configuration Management**
- ✅ Create new financial configurations
- ✅ Set up personal information (birth year, retirement age, death age)
- ✅ Configure risk tolerance and financial goals

**Income & Expense Tracking**
- ✅ Add income sources (salary, pension, benefits)
- ✅ Track expenses and recurring costs
- ✅ Support for monthly/yearly frequency conversion

**Life Event Planning**
- ✅ Retirement planning with automatic income transitions
- ✅ Children planning with barnetrygd and expenses
- ✅ Inheritance and property change events

### 🗣️ **Natural Language Examples**

**Asset Creation with Loans (English)**
```
"Add a Tesla worth 500K with a loan of 300K for 5 years"
"Create a house valued at 3M with a mortgage of 2.5M for 25 years"
"Add a BMW X5 worth 800K with loan 500K over 6 years"
```

**Asset Creation with Loans (Norwegian)**
```
"Legg til en tesla til en verdi av 200K med et lån på 100K over 7 år"
"Opprett et hus verdt 3M med et boliglån på 2,5M i 25 år"
"Legg til en BMW X5 til verdi 800K med lån 500K over 6 år"
```

**Mortgage Updates (English)**
```
"Set mortgage on my house to 2,500,000 NOK"
"Update mortgage interest on my cabin to 5.2%"
"Change mortgage on my house to 3M NOK with 4.5% interest for 25 years"
```

**Mortgage Updates (Norwegian)**
```
"Sett lånet på mitt hus til 2 500 000 kroner"
"Oppdater boliglån rente på min hytte til 5,2%"
"Endre lånebeløpet på min hytte til 1 800 000"
```

**Asset Value Updates (English)**
```
"Update my house value to 3.5M NOK"
"Set my Tesla value to 450,000 NOK"
"Change my cabin worth to 1.2M"
```

**Asset Value Updates (Norwegian)**
```
"Oppdater verdien på mitt hus til 3,5M kroner"
"Sett verdien på min Tesla til 450 000 kroner"
"Endre verdien på min hytte til 1,2M"
```

**Configuration Creation (English)**
```
"Create a new configuration for John Doe, born 1985, wants to retire at 60, expects to live until 85"
"Set up financial planning for someone born in 1990 with moderate risk tolerance"
```

**Configuration Creation (Norwegian)**
```
"Opprett en ny konfigurasjon for Kari Nordmann, født 1985, vil pensjonere seg ved 60, forventer å leve til 85"
"Sett opp finansiell planlegging for noen født i 1990 med moderat risikotoleranse"
```

**Income Management (English)**
```
"Add salary income of 50,000 NOK per month"
"Create pension income of 25,000 NOK monthly starting at age 67"
"Add barnetrygd for 2 children"
```

**Income Management (Norwegian)**
```
"Legg til lønnsinntekt på 50 000 kroner per måned"
"Opprett pensjonsinntekt på 25 000 kroner månedlig fra 67 år"
"Legg til barnetrygd for 2 barn"
```

### 🔧 **Technical Features**

**Smart Data Extraction**
- ✅ **K/M Multipliers**: Understands "100K" = 100,000 and "2.5M" = 2,500,000
- ✅ **Currency Recognition**: Handles NOK, kroner, and various formats
- ✅ **Asset Identification**: Recognizes brands (Tesla, BMW, etc.) and types
- ✅ **Time Periods**: Extracts loan terms like "over 7 år" or "for 5 years"

**Multi-Language Support**
- ✅ **English & Norwegian**: Full support for both languages
- ✅ **Mixed Language**: Can handle mixed language inputs
- ✅ **Cultural Formats**: Norwegian number formatting (space as thousand separator)

**Context Awareness**
- ✅ **Configuration Context**: Remembers current working configuration
- ✅ **Asset Relationships**: Understands "my house", "min hytte", etc.
- ✅ **Progressive Collection**: Asks for missing information step-by-step

**Real-Time Processing**
- ✅ **Status Updates**: Shows processing steps with animations
- ✅ **Instant Feedback**: Immediate confirmation of actions
- ✅ **Error Handling**: Graceful error messages and suggestions

### 🎨 **User Interface**

**Floating Assistant Widget**
- ✅ **Always Available**: Accessible from any page in the application
- ✅ **Animated Interactions**: Smooth animations and visual feedback
- ✅ **Conversation History**: Maintains context throughout the session
- ✅ **Responsive Design**: Works on desktop and mobile devices

**Status Indicators**
- ✅ **Processing Steps**: Shows what the AI is currently doing
- ✅ **Progress Animations**: Pulsing text, bouncing dots, shimmer effects
- ✅ **Completion Feedback**: Clear confirmation when tasks are complete

### 🧪 **Quality & Testing**

**Comprehensive Test Coverage**
- ✅ **Intent Recognition**: 100% success rate on 63+ test cases
- ✅ **Data Extraction**: Validates all parsing scenarios
- ✅ **Database Integration**: Ensures proper data storage
- ✅ **Multi-Language**: Tests both English and Norwegian patterns

**Error Handling**
- ✅ **Graceful Degradation**: Handles unknown requests politely
- ✅ **Validation**: Ensures data integrity before database operations
- ✅ **User Guidance**: Provides helpful suggestions when requests fail

### 🚀 **Getting Started**

**Accessing the AI Assistant**
1. **Open the Widget**: Click the sparkle (✨) button in the bottom-right corner
2. **Start Typing**: Use natural language to describe what you want to do
3. **Follow Prompts**: The AI will ask for any missing information
4. **Confirm Results**: Review the changes and continue with your workflow

**Best Practices**
- ✅ **Be Specific**: Include amounts, time periods, and asset names
- ✅ **Use Natural Language**: Don't worry about exact syntax
- ✅ **Check Results**: Review the AI's understanding before confirming
- ✅ **Ask for Help**: Type "help" to see available features

**Example Workflow**
```
User: "Legg til en tesla til en verdi av 200K med et lån på 100K over 7 år"
AI: ✅ Tesla Car added with value 200 000 NOK

User: "Update mortgage interest on my Tesla to 4.5%"
AI: ✅ Tesla Car mortgage updated:
    💰 Amount: 100 000 NOK
    📈 Interest Rate: 4.5%
    📅 Term: 7 years
```

The AI assistant makes financial planning accessible and intuitive, allowing you to focus on your financial goals rather than learning complex interfaces.


Logo Concepts:
Integrated Financial Path:

A circular logo with a graph or path that spirals inward, representing the detailed, year-by-year prognosis the software provides. The path could start wide and gradually narrow as it reaches the center, indicating a focused and precise outcome.
Growing Wealth Tree:

A tree with leaves that look like coins or currency symbols. The roots could represent the different types of taxes and financial metrics the software takes into account, showing that wealth is deeply rooted in careful planning and consideration of all financial aspects.
Shield with Graph:

A shield symbolizing security, with an upward trending graph within it. This could reflect the dual focus on growing wealth while protecting it through thorough analysis and planning.
Crystal Ball with Calculator:

A crystal ball that has a subtle outline of a calculator within it, symbolizing the foresight and precision of the software in predicting financial outcomes.
Circle with Tax Icons:

A circle with various small icons around its border representing different taxes and financial elements (e.g., a small house for property tax, a bag of money for income tax), with a central icon that represents overall wealth, like a growing graph or tree.

---

## Queue Worker Setup

This application uses Laravel queues to handle long-running tasks like AI analysis. The queue is configured to use the database driver.

### Starting the Queue Worker

To process background jobs, you need to run the queue worker:

```bash
php artisan queue:work --tries=3 --timeout=300
```

**Options:**
- `--tries=3` - Retry failed jobs up to 3 times
- `--timeout=300` - Allow jobs to run for up to 5 minutes (300 seconds)

### For Production

For production environments, you should use a process manager like **Supervisor** to keep the queue worker running:

1. Install Supervisor:
```bash
sudo apt-get install supervisor
```

2. Create a configuration file at `/etc/supervisor/conf.d/wealthprognosis-worker.conf`:
```ini
[program:wealthprognosis-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan queue:work --tries=3 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/worker.log
stopwaitsecs=3600
```

3. Start Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wealthprognosis-worker:*
```

### Monitoring Jobs

- View failed jobs: `php artisan queue:failed`
- Retry failed jobs: `php artisan queue:retry all`
- Clear failed jobs: `php artisan queue:flush`

### Important Notes

- The queue worker must be running for AI analysis and other background tasks to work
- After code changes, restart the queue worker: `php artisan queue:restart`
- Monitor the `jobs` and `failed_jobs` tables in your database
