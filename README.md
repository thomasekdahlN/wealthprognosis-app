<meta name="google-site-verification" content="sVU5VtIVFfRVyzYylywIA2XMMxLQez6ehHqDndMjI1M" />
<img src="public/logo.png" alt="Logo" width="200"/>

## Wealth prognosis predicts your yearly future economy from now until you die

Makes a qualified prognosis of your economic future year for year until your death. It takes into account your income, expences, assets/liabilities, loans, taxes, inflation and more.

Configure all your assets, mortgage, income and expences.

The program takes into account all known taxes in Norway, like fortune tax, property tax, income tax, capital tax, pension tax, rental tax, company tax, dividend tax, interest tax, wealth tax, inheritance tax, gift tax, sales tax and tax shield and calculates it for every asset every year
The program looks at how much your max possibel mortgage can be

The program looks at different F.I.R.E metrics so you can see when you can become financially independent and retire early. But recommends a different approach than the 4% rule - it will take the number of years from you wish to retire until the year you die - and sell the sellable assets until zero (fully configurable). You will still have your house, car, boat and cabin left after these sales.
Supports correct taxation when transfering an asset from a company to private (first correct taxation on realization in company, then correct taxation when transfering to private)

Supports normal annuitets mortgage and extra downpayments.

On each asset you can do a rule based addition or subtraction, like adding 5000 to a equity fund every month.
On each asset you can do an calculation based on other assets value and add it to this asset. Like taking 5% of the salary and add it to OTP (this does not change your salary but it increases your OTP)
On each asset you can do an transfer to another asset. This will correctly calculate the taxes for the sale involved before transfer. 

#### Licence
The code is under GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007. Free for personal usage and free for non-commercial usage (that means that you do not make money on this software). Contributors wanted. Commercial usage is allowed with a commercial licence - please contact me to obtain a commercial usage. If you want to pay for new functionality, please feel free to contact me.

#### Examples
- Like taking 1/15 part of a equity fund each year and add it to your income for spending
- taking 50% of the positive cachflow of one asset to make a extra mortgage downpayment for finish mortgage earlier.
- Take 100% of the sales value of a stock/company and transfer it to a equity fund on your exit from the stock.
- Transfer the value from one asset to another (to simulate if it gives you more wealth)
- Children asset has both income and expences until the children move out, then they are removed from your economy
- Public pension is added to your income the year you pension
- Public pension is added to your income the year you pension until you ar 77 years.

Your asset configuration can then be run with different prognossis, like realistic, positive, negative, tenpercent, zero, variable, all, private, company.
Each asset can have a changerate, that can be different for each year on how the asset behaves. The different prognosis configurations has different yearly change paths for each type of asset or you can make your own.
Assets can both increase and decrease in value based on the changerates.

#### Example: 
- Change path for interest can på 4% in 2023, 6% in 2024 and then 5% in 2025, etc
- Change path for your car or boat would be -10% in 2023, -10% in 2024 and then -10% in 2025, etc
It then sums everything up and shows you all the details on how your economy behaves.

It outputs a very detailed excel spreadsheet of your future economy prognosis where you can see how well a single asset performs, or you can look at the total performance for your private or company economy - or the sum of your private and company economy. Se examples and definitions below.
It also outputs a page with the spread of your different asset types - so you can see where you are most heavily invested.

Note II: A transfer should always be done to an asset later in the config file, since the assets are calculated in order of appearance in the config file. A source should alsways be retrieved from an asset earlier in the config file.
Note: This is just a hack and is not production ready, but its already useful.

#### Special asset names:
total, company (total company summary), private (total private summary), income (private collecting all income from assets that are taxed - not same as salary which will be taxed)

### Examples:

* [boat](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [car](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [cash](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [child](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [company](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [crypto](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [example_simple](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [example_advanced](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [fond](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [house](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [inheritance](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [kpi](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [otp](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [pension](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [property](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [property-mortgage-interest-only-vs-fond](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [property-mortgage-vs-fond](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [property-rental](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [rental-vs-fond](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [salary-otp](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.

### How to run
php artisan ReadFile2 yourassetconfig.json realistic|positive|negative|tenpercent|zero|variable all|private|company

php artisan ReadFile2 tests/Feature/config/example.json realistic private

tests/Feature/config/example.json = path to your asset definition.
realistic|positive|negative are standard prognosis. You can copy and make your own, just place them in the same directory. tenpercent/zero/variable is just for manual testing.
all|private|company - run the prognosis for only, private, only company or both.

Output:
The command will automatically generate [excel](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.

Reads your economic setup as a json file and provides a detail spreadsheet with analysis of your future economy until your death.

### Supports
- Supports % changerates on expences, income and asset values
- Supports mortage calculations and new mortages taking over for a previous mortage
- Support incrementell decrease of income / expence / asset (using negative amount as input)
- Supports calculation of fortune tax in norway (but not adding it to expences)
- Calculates all values for all assets
- Groups assets into groups for group overview
- Groups assets into total, company and private for a total overview of your economic future
- Estimates your max loan capasity from banks.

### F.I.R.E calculations
- Calculate 4% retirement on asset cash flow (FIRE)
- F.I.R.E income = income + asset value + deductable taxes + loan principal
- F.I.R.E expence = expence + mortgage + taxable taxes
- F.I.R.E cashflow = FIRE income - FIRE expence amount
- F.I.R.E % = FIRE income / FIRE expence = How close you are to fire
- F.I.R.E SavingRate = FIRE cashflow / FIRE income (in progress)

### Support for more sophisticated dynamics in income/expence/asset - 

rule - support:
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

### Supported assets types for prognosis and tax calculation
* ask - Aksjesparing med skattefradrag
* boat - Båt
* cabin - Hytte
* car - Bil
* bank - Bankkonto
* cash - Kontanter[wealth-te-do-not-share-2024-03.json](..%2F..%2FDocuments%2Fwealthprognosis%2Fwealth-te-do-not-share-2024-03.json)
* child - Barn. Skattefritt men kjekt å klassifisere.
* crypto - Krypto
* bondfund - Rentefond
* equityfund - Aksjefond eller fond som det kalles på, populært
* gold - Gull
* inheritance - Arv.
* ips - Pensjonssparing med spesielle skatteregler.
* loantocompany - Når du har gitt et lån til et firma (Skjermingsfadrag
* income - her samler vi inntekt fra alle assets. Det blir aldri regnet skatt her. income regnes som ferdig skattet fra en transfer/rule/source fra annen asset
* kpi - Konsumpris indeksen. For å se om investeringene dine gjør det bedre eller dårligere enn denne.
* otp - Offentlig tjenestepensjon
* pension - public pension / folketrygd
* property - Eiendom du ikke leier ut. Sekundærboliger.
* rental - Utleieeiendom
* salary - Lønn
* soleproprietorship - Enkeltpersonforetak
* stock - Aksjer. Må hensynta fritaksregelen. Ingen skatt på salg av aksjer fra et firma, kun skatt ved salg av aksjer for privatpersoner

### Functionality on the priority wishlist:
- Check calculations for property tax
- Check calculations for bracket tax
- Support for personfradrag in tax calculations - calculating a bit too high now
- Support for reading tax configurations pr year and country (support for more than norwegian tax regime). Only using the current years tax regime for all calculations now
- Support for yearly tax on interest on money in the bank.
- Support for two different types of stock - sellable and non-sellable (greymarket). Sellable stocks will be sold until zero when you retire. Non-sellable stocks will be kept until you die.
- Fortune tax is divided into state and municipality tax. Should be calculated separately.

#### Not a priority, but have been thinking of it.
- Support for factor on a rule like +1000
- Support for changerates on rules, like adding 5% to the +1000 rule each year.
- Support for monstertax on property/boat/airplane/helicopters
-  Company fortune should be retrieved from the previous year not the current year (tax vise)
- //https://www.skatteetaten.no/person/skatt/hjelp-til-riktig-skatt/verdsettingsrabatt-ved-fastsetting-av-formue/
- Calculate only 1 year of a mortgage at a time, to avoid this vertical processing problem.
- All transfers to next year? (to avoid vertical processing problem)
- Extra nedbetaling av lån skaper masse utfordringer (fordi det påvirker mange verdier som allerede er beregnet og må reberegnes)
- Should support different tax types within the asset, like separate tax for income vs asset value
- Catch 22. If calculating otp from salary we can not transfer to salary from otp because of sequenze problems. Have to add a "income" type at the end of the config to add all such transfers, to split between salary and income (from investements)
- Når man betaler ned et lån og det blir penger igjen etter extraDownpayment så repeteres ikke det gjenværende beløpet på asset'en den kom fra. Både riktig og galt når repeat er false.... Men reglene skal ikke repeteres (eller må vi ha separat repeat på ulike deler)
- Supporting tax prognosis, not just use this years taxes
- Property tax should use the tax value of year-2 (Holmestrand at least))
- Company fortune tax for private person should use the tax value of year-2 
- rename group => owner [private|company]
- Support for selling parts of partsellable assets every year to get the cashflow to zero. (has top calculate reversed tax - the amount you neet to pay + tax has to be transfered to cashflow)
- Tax configuration pr year and countries (support for more than norwegian tax regime). Only using the current years tax regime for all calculations now
- Take into account the number of years you have owned an asset regardign tax calculation on i.e house and cabin.
- Gjøre beregningene pr år så asset, ikke asset pr år som nå (da vil ikke verdiøkning o.l være med) (BIG REFACTORING - but cod is prepared for it)- 
- Klassifisere F.I.R.E oppnåelse pr år
- Showing all values compared to KPI index (relative value) and how we perform compared to kpi
- Refactoring and cleanup of code
- More TDD / tests
- F.I.R.E - Use up 4% of partly sellable assets from wishPensionYear to DeathYear to see how it handles. Not needed anymore since using up a divisor of your assets (1/10) until you die is a better way to use up sellable assets.
- Retrieving asset values from API, like Crypto/Fond/stocks

## Config

### meta - top level - reserved keyword
- meta.name - Required. Your name or an alias for you
- meta.birthYear - Required. When you are born
- meta.prognoseYear - Just visualizes this year extra with a colored line in excel
- meta.pensionOfficialYear - Official pension year in your country (67 in noprway)
- meta.pensionWishYear - When you wish to retire. Maybe you want to retire earlier because of F.I.R.E
- meta.deathYear - Required. When do you think you die?
- meta.exportStartYear - Optional. Defaults to last year. The excel sheet created will start at this year.

Your pensionOfficialYear/pensionWishYear will be used to calculate equal payments (like 1/14 of your assets) from your assets until deathYear. So if you live longer, you get less pr year.

### Assets configurations

NOTE: Asset name has to be unique, and is used to identify the asset in all calculations.

#### meta - asset level - reserved keyword

- meta.type - Required. What kind of asset this is. Valid values income|expence|mortgage|asset|pension|otp|fond|cash|house|car|inheritance|boat|cabin|crypto|pension|property|rental|salary
- meta.group - Required. Valid values private|company asset
- meta.name - Required. Shor description of the asset, used in excel tabs
- meta.description - Optional. Longer description of your asset.
- meta.active - Required. Valid values true|false. If false, the asset will not be calculated.
- meta.tax - Required. How this asset is taxed. Valid values income|fond|cash|house|car|inheritance|boat|cabin|crypto|pension|property|rental|salary|none. What kind of tax is this asset subject to.

#### Income
- income.amount - beløp før skatt
- income.changerate - endring i prosent eller variabel hentet fra config fil for prosent f.eks changerates.kpi, changerates.fond, changerates.otp, changerates.house, changerates.car, changerates.cash
- income.rule - regler for hvordan inntekten skal behandles. Se eget kapittel for syntax
- income.transfer - overføring av inntekt til en annen asset, dvs den flytter pengene fra denne asset til den asset som er oppgitt i transfer. Merk at en transfer må beregnes før asset den overføres til.
- income.source - rule beregning av et beløp i en annen asset, som skal legges til denne. Merk at en source må beregnes etter asset den henter verdier fra. Endrer ikke verdien i source.
- income.repeat - true/false - Hvis true gjenta konfigurasjonen inntil den blir overskrevet av en annen konfigurasjon et senere år.
- income.description - beskrivelse av inntekten

#### Expence
- expence.amount - beløp før skatt
- expence.changerate - endring i prosent eller variabel hentet fra config fil for prosent f.eks changerates.kpi, changerates.fond, changerates.otp, changerates.house, changerates.car, changerates.cash
- expence.rule - regler for hvordan inntekten skal behandles. Se eget kapittel for syntax
- expence.transfer - overføring av inntekt til en annen asset, dvs den flytter pengene fra denne asset til den asset som er oppgitt i transfer. Merk at en transfer må beregnes før asset den overføres til.
- expence.source - rule beregning av et beløp i en annen asset, som skal legges til denne. Merk at en source må beregnes etter asset den henter verdier fra. Endrer ikke verdien i source.
- expence.repeat - true/false - Hvis true gjenta konfigurasjonen inntil den blir overskrevet av en annen konfigurasjon et senere år.
- expence.description - beskrivelse av utgiften

#### mortgage - Lån
- mortgage.amount - Required. The original mortgage amount
- mortgage.interest - Required. rente i prosent. Recommended to use "changerates.interest" to get the interst prediction pr year and not hardcode it.
- mortgage.years - Required. Hvor mange år skal lånet være
- mortgage.interestOnlyYears - Optional. Hvor mange år lånet skal være avdragsfritt og man bare betaler renter. Må være mindre enn mortgage.years. Hvis ikke angitt, betales renter og avdrag for mortgage.years
- mortgage.gebyr - gebyr pr år
- mortgage.extraDownpaymentAmount - årlig ekstra nedbetaling på lån hele lånets løpetid. Forkorter lånets løpetid om beløpet er stort nok.
- mortgage.description - beskrivelse av lånet

#### asset
- asset.marketAmount - Required. Markedsverdien på en asset. This is the main value we use when talking about an asset.
- asset.acquisitionAmount - Optional. Anskaffelsesverdi. Blir default satt. Vi trenger å vite denne for å skatteberegne ved realisasjon, da det ofte trekkes fra før skatt. F.eks verdi på hus ved kjøp.
- asset.equityAmount - Optional. Egenkapital : Blir default satt til asset.acquisitionAmount - mortgage.balanceAmount (hensyntar da automatisk ekstra nedbetalign av lån). Legger også til ekstra overføringer fra rule eller transfer regler som egenkapital.
- asset.paidAmount - Optional. Finanskostnader. Blir default satt til asset.marketAmount hvis ikke angitt. Brukes hvsi du har betalt noe annet enn markedsverdi, f.eks ved arv.
- asset.taxableInitialAmount - Optional. Skattbart beløp ikke hensyntatt lån. Blir default satt til asset.marketAmount. Antall kroner av markedsverdien til en asset det skal skattes av. F.eks en hytte kan ha mye lavere skattbar verdi enn markedsverdien minus verdsettelsesrabatt. Blir justert med changerate til asset.
- asset.changerate - endring i prosent eller variabel hentet fra config fil for prosent f.eks changerates.kpi, changerates.fond, changerates.otp, changerates.house, changerates.car, changerates.cash
- asset.rule - regler for hvordan inntekten skal behandles. Se eget kapittel for syntax
- asset.transfer - overføring av inntekt til en annen asset, dvs den flytter pengene fra denne asset til den asset som er oppgitt i transfer. Merk at en transfer bør beregnes før asset den overføres til. Hvis du overfører til en som allerede er beregnet, så blir den ikke reberegnet
- asset.source - rule beregning av et beløp i en annen asset, som skal legges til denne. Merk at en source må beregnes etter asset den henter verdier fra. Endrer ikke verdien i source.
- asset.repeat - true/false - Hvis true gjenta konfigurasjonen inntil den blir overskrevet av en annen konfigurasjon et senere år.
- asset.description - Beskrivelse av asset/liability

### Output: Datasettet vi regner på pr år

#### Income
- income.amount - beløp før skatt
- income.changerate - endring i prosent
- changeratePercent
- income.rule - regler for hvordan inntekten skal behandles
- income.transfer - overføring av inntekt til en annen asset
- income.repeat - gjenta konfigurasjonen for kommende år
- income.description - beskrivelse av inntekten
- income.transferedAmount - Hva du har overført til/fra income (fra transfer, source eller rule). Ikke changerate endringer.

#### Expence
- expence.amount - beløp før skatt
- expence.changerate - endring i prosent
- changeratePercent
- expence.rule - regler for hvordan utgiften skal behandles
- expence.transfer - overføring av inntekt til en annen asset
- expence.repeat - gjenta konfigurasjonen for kommende år
- expence.description - beskrivelse av utgiften
- expence.transferedAmount - Hva du har overført til/fra expence (fra transfer, source eller rule). Ikke changerate endringer.

#### Cashflow
- cashflow.afterTaxAmount = income.amount - expence.amount - cashflowTaxAmount - asset.taxAmount - mortgage.termAmount + mortgage.taxDeductableAmount- tax taken into account
- cashflow.beforeTaxAmount = cashflow.beforeTaxAmount - cashflow.taxYearlyAmount - Tax not calculated
- cashflow.beforeTaxAggregatedAmount += cashflow.beforeTaxAccumulatedAmount
- cashflow.afterTaxAggregatedAmount += cashflow.afterTaxAccumulatedAmount
- cashflow.taxAmount - skatt beløp (could be positive or negative, deponds on income positive opr negative)
- cashflow.taxDecimal - skatt prosent
- cashflow.transferedAmount - Beløp du har overført til/fra. (fra transfer, source eller rule). Ikke changerate.
- cashflow.rule - regler for hvordan beløpet skal beregnes
- cashflow.transfer - overføring av positiv cashflow til en annen asset
- cashflow.repeat - gjenta konfigurasjonen [cashflow.rule, cashflow.transfer, cashflow.repeat]for kommende år
- cashflow.description - beskrivelse av cashflow

#### mortgage - Lån
- mortgage.amount - The original mortgage amount (the same for every year, for reference and easy calculation)
- mortgage.termAmount - Nedbetaling av lån pr år ihht betingelsene (renter + avdrag + gebyr) = interestAmount + principalAmount + gebyrAmount
- mortgage.interestAmount - renter - i kroner pr år
- mortgage.principalAmount - Avdrag - i kroner pr år (det er dette som nedbetaler lånet)
- mortgage.balanceAmount - gjenstående lån i kroner
- mortgage.extraDownpaymentAmount - ekstra nedbetaling av lån pr år (Utgår nå som vi har: transferedAmount?)
- mortgage.transferedAmount - Hva du har overført til/fra mortgage
- mortgage.interest - rente i prosent (Brukes i reberegning ved ekstra nedbetaling av lån)
- mortgage.interestDecimal - rente i desimal
- mortgage.years - Gjenværende atnall år løpetid på lånet, basert på første konfigurasjon av lånet. Med ekstra nedbetalign vil lånet kunne bli betalt ned på færre antall år om ekstra innbetalingsbeløpene er store nok
- mortgage.gebyrAmount - gebyr pr år
- mortgage.taxDeductableAmount - fradrag
- mortgage.taxDeductableDecimal - fradrag i prosent
- mortgage.description - beskrivelse av ektsra hendelser i låneberegningen.

#### asset
- asset.marketAmount - Markedsverdien på en asset
- asset.marketMortgageDeductedAmount - Markedsverdien ved salg hensyntatt restlån men ikke skatt : asset.amount - mortgage.balanceAmount 
- asset.acquisitionAmount - Anskaffelsesverdi. Vi trenger å vite denne for å skatteberegne ved realisasjon, da det ofte trekkes fra før skatt. F.eks verdi på hus ved kjøp.
- asset.acquisitionInitialAmount - Settes bare første gang vi ser beløpet i det året vi ser det. For å kunne rekalkulere med transferedAmount senere
- asset.equityAmount - Egenkapital : asset.acquisitionAmount - mortgage.balanceAmount (hensyntar da automatisk ekstra nedbetalign av lån). Legger også til ekstra overføringer fra rule eller transfer regler som egenkapital.
- asset.equityInitialAmount - Settes bare første gang vi ser beløpet i det året vi ser det. For å kunne rekalkulere med transferedAmount senere
- asset.paidAmount - Finanskostnader. Hva du faktisk har betalt, inkl renter, avdrag, gebur, ekstra innbetaling på lån og ekstra kjøp.
- asset.paidInitialAmount - Settes bare første gang vi ser beløpet i det året vi ser det. For å kunne rekalkulere med transferedAmount senere
- asset.transferedAmount - Hva du har overført til/fra denne asset. Kan være både positivt og negativt beløp.  (fra transfer, source eller rule). Ikke changerate.
- asset.mortageRateDecimal- Hvor mye i % av en asset som er lånt. Belåningsgrad. 
- asset.taxableDecimal - Skattbar prosent - Antall prosent av markedsverdien til en asset det skal skattes av
- asset.taxableAmount - Skattbart beløp - Antall kroner av markedsverdien til en asset det skal skattes av minus lån. Denne er dynamisk og regnes ut fra asset.taxableInitialAmount - mortgage.balanceAmount. Kan ikke overstyres direkte.
- asset.taxableInitialAmount - Skattbart beløp før lånet er trukket fra. Dvs det er det samme som asset.taxableAmount hvis det ikke er lån, men vi må holde det tilgjengelig og justere det for å kunne finne det igjen når et lån er nedbetaøt. Trenger aldri vises. Kun for beregninger. Blir justert årlig.
- asset.taxableAmountOverride - Auto: Set to true for all coming years if it finds a asset.taxableAmount the first year.
- asset.taxDecimal - Formuesskatt. Prosent skatt på asset op en assets skattbare verdi
- asset.taxAmount - Formuesskatt. Kroner skatt på asset op en assets skattbare verdi
- asset.changerate - Hvor mye en asset endrer seg i verdi pr år
- changeratePercent
- asset.rule
- asset.transfer
- asset.repeat
- asset.taxablePropertyDecimal -  Skattbar prosent - Antall prosent av markedsverdien til en asset det skal beregnes eiendomsskatt av
- asset.taxablePropertyAmount- Skattbart beløp - Antall kroner av markedsverdien til en asset det skal betales eiendomsskatt av (både % og bunnfradrad hensyntatt)
- asset.taxPropertyAmount - Eiendomsskatt i kroner. Beregnes av asset.marketAmount.
- asset.taxPropertyDecimal - Eiendomsskatt i prosent
- asset.description - Beskrivelse av asset/liability

#### realization (Really a part of asset, but we keep the structure simpler by having it separate). This is what happens if we sell the asset. It does not meen we have sold it, sale is done with a transfer to another asset.
- realization.amount - Beløpet man sitter igjen med etter et salg = asset.marketAmount - asset.realizationTaxAmount
- realization.taxableAmount - Skattbart beløp ved realisering av asset = asset.marketAmount - asset.acquisitionAmount
- realization.taxAmount - Skattbart beløp ved realisering av asset = asset.realizationTaxableAmount * asset.realizationTaxDecimal - realization.taxShieldAmount
- realization.taxDecimal - Skattbar prosent ved realisering av asset. Lest fra tax.json
- realization.taxShieldAmount - Skjermingsfradrag beløp (Akkumuleres hvis ubenyttet, reduseres automatisak hvis benyttet)
- realization.taxShieldDecimal - Skjermingsfradrag prosent
- realization.description - Beskrivelse av salg/realisasjon av asset

#### Yield
- yield.bruttoPercent = (income.amount / asset.acquisitionAmount) * 100
- yield.nettoPercent = ((income.amount - expence.amount) / asset.acquisitionAmount) * 100

#### Potential
How much potential the bank sees in your income - expences
- potential.incomeAmount - On rental it accounts for 10 out of 12 months rented out, then subtracts the mortgage.termAmount (since an existing mortgage reduces your mortgage potential)
- potential.mortgageAmount - Hvor mye du potensielt kan låne. debtCapacity?


#### fire (F.I.R.E) - beregnes på income, expence, asset, mortgage, cashflow
Før eller etter skatt her?
- fire.percent - % uttaket du vil ta fra assetsa når FIRE er oppnådd.
- fire.incomeAmount - F.I.R.E inntekt - Basert på 4% uttak av assets som er definert i $firePartSalePossibleTypes. Dvs det du kan leve av av sparemidler. Har en del spørsmål her mtp fratrekk av lån/renter/skatt 
- fire.expenceAmount - F.I.R.E utgift - Dine faktiske utgifter ihht config
- fire.rateDecimal - fire.incomeAmount / fire.expenceAmount . Hvor nærme du er å nå FIRE 
- fire.cashFlowAmount - fire.incomeAmount - fire.expenceAmount
- fire.savingAmount - sparebeløp. Hvor mye du sparer pr år. Medberegnet avdrag men ikke renter.Regnes på assets av typen $fireSavingTypes[house, rental, cabin, crypto, fond, stock, otp, ask, pension]
- fire.savingRateDecimal - fire.savingAmount (hvor mye som regnes som sparing) / income.amount (mot dine totale inntekter)

### Example simple config
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
