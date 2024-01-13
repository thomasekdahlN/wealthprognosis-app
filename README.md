## Wealth prognosis predicts your yearly future economi from now until you die

Predicts your economic future and wealth taking into consideration all known parameters, and calculates it for yearly as long as you wish

Note: This is just a hack and is not production ready, but its already useful.

Configure all your assets value, mortgage, income and expences. Run different standard prognosis like negative, normal or positive and see how well your assets behave.

It outputs a very detailed excel spreadsheet of your future economy. 

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
* [inheritance](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [kpi](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [otp](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [pension](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [property](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [rental](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.
* [salary](https://github.com/thomasekdahlN/wealthprognosis-app/blob/main/tests/Feature/config/example_simple_tenpercent.xlsx) files in the same directory as your config file, with the same name as the run config file.

### How to run
php artisan ReadFile2 yourassetconfig.json realistic/positive/negative/tenpercent/zero/variable all/private/company

php artisan ReadFile2 tests/Feature/config/example.json realistic private

tests/Feature/config/example.json = path to your asset definition.
realistic/positive/negative are standard prognosis. You can copy and make your own, just place them in the same directory. tenpercent/zero/variable is just for manual testing.
all/private/comapny - run the prognosis for only, private, only company or both.

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
-- +10% - Adds 10% to amount
-- -10% - Subtracts 10% from amount
-- 10% - Calculates 10% of the amount
-- +1000 - Adds 1000 to amount
-- -1000 - Subtracts 1000 from amount
-- +1/10 - Adds 1 tenth of the amount yearly
-- -1/10 - Subtracts 1 tenth of the amount yearly
-- 1/10 - Calculates 1/10 of the amount. Does not change the amount
-- +1|10 - Adds 1 tenth of the amount yearly, and subtracts nevner with one(so next value is 1/9, then 1/8, 1/7 etc)
-- -1|10 - Subtracts 1 tenth of the amount yearly. Then subtracts nevner with one. (so next value is 1/9, then 1/8, 1/7 etc). Perfect for usage to i.e empty an asset over 10 years.
-- 1|10 - Calculates 1|10 of the amount. Does not change the amount.

**source**

Default den asset man står på, medmindre annet er spesifisert. Brukes for å beregne beløpet basert på verdiene i en annen asset enn den man står på. Will not reduce the value of the source asset,

**transfer**

Overføring av beløp fra den asset regelen er på til den asset som er spesifisert i regelen,
Beløp blir kun overført hvis det er spesifisert en transfer på asset som skal sende beløpet, hvis ikke blir beløpet lagt til den asset man står på.

### Supported assets for prognosis and tax calculation
* boat
* cabin
* car
* cash
* child
* stock - Må hensynta fritaksregelen. Ingen skatt på salg av aksjer.
* crypto
* gold
* fond
* inheritance
* income
* kpi
* otp
* pension - public pension
* property
* rental
* salary

### Functionality on the priority wishlist:
- Ny konfig: Simulere på å bare betaler renter på lån og putte avdrag i fond, vs å betale ned lån.
- ekstra nedbetaling på lån basert på en årlig variable ekstra innbetaling på lån fra en rule eller en transfer fra en annen asset
- overføre % beløp fra cashflow til asset og reberegne asset (refactoring til income/expence/asset metoder for beregning med år som input)
- 
- 
- Support for skjermingsfradrag

#### Not a priority, but have been thinking of it.
- Gjøre beregningene pr år så asset, ikke asset pr år som nå (da vil ikke verdiøkning o.l være med) (BIG REFACTORING - but cod is prepared for it)- 
- Klassifisere F.I.R.E oppnåelse pr år
- Showing all values compared to KPI index (relative value) and how we perform compared to kpi
- Tax configuration pr year and countries (support for more than norwegian tax regime)
- Refactoring and cleanup of code
- Retrieving asset values from API, like Crypto/Fond/stocks
- Summere riktig skattefradrag basert på rente og alder på hus og hytter 
- Support for property tax with different setting spr asset (du to different places having different taxes. Both tax percent and standardDeduction). Deduction for property tax for rentals is not handled.
- F.I.R.E - Use up 4% of partly sellable assets from wishPensionYear to DeathYear to see how it handles. Not needed anymore since using up a divisor of your assets (1/10) until you die is a better way to use up sellable assets.

## Config

### meta - top level - reserved keyword
- meta.name - Required. Your name or an alias for you
- meta.birthYear - Required. When you are born
- meta.prognoseYear - Just visualizes this year extra with a colored line in excel
- meta.pensionOfficialYear - Official pension year in your country (67 in noprway)
- meta.pensionWishYear - When you wish to retire. Maybe you want to retire earlier because of F.I.R.E
- meta.deathYear - Required. How long do you think you live.

Your pensionOfficialYear/pensionWishYear will be used to calculate equal payments (like 1/14 of your assets) from your assets until deathYear. So if you live longer, you get less pr year.

### Assets configurations

NOTE: Asset name has to be unique, and is used to identify the asset in all calculations.

#### meta - asset level - reserved keyword

- meta.type - Required. What kind of asset this is. Valid values income|expence|mortgage|asset|pension|otp|fond|cash|house|car|inheritance|boat|cabin|crypto|pension|property|rental|salary
- meta.group - Required. Valid values private|company asset
- meta.name - Required. Shor description of the asset, used in excel tabs
- meta.description - Optional. Longer description of your asset.
- meta.active - Required. Valid values true|false. If false, the asset will not be calculated.
- meta.tax - Required. How this asset is taxed. Valid values income|fond|cash|house|car|inheritance|boat|cabin|crypto|pension|property|rental|salary. What kind of tax is this asset subject to.

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
- asset.paidAmount - Optional. Blir default satt til asset.marketAmount hvis ikke angitt. Brukes hvsi du har betalt noe annet enn makredsverdi, f.eks ved arv.
- asset.taxableAmount - Optional. Skattbart beløp. Blir default satt til asset.marketAmount. Antall kroner av markedsverdien til en asset det skal skattes av. F.eks en hytte kan ha mye lavere skattbar verdi enn markedsverdien minus verdsettelsesrabatt.
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
- income.rule - regler for hvordan inntekten skal behandles
- income.transfer - overføring av inntekt til en annen asset
- income.repeat - gjenta konfigurasjonen for kommende år
- income.description - beskrivelse av inntekten
- income.transferedAmount - Hva du har overført til/fra income (fra transfer, source eller rule). Ikke changerate endringer.

#### Expence
- expence.amount - beløp før skatt
- expence.changerate - endring i prosent
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
- asset.equityAmount - Egenkapital : asset.acquisitionAmount - mortgage.balanceAmount (hensyntar da automatisk ekstra nedbetalign av lån). Legger også til ekstra overføringer fra rule eller transfer regler som egenkapital.
- asset.paidAmount - Hva du faktisk har betalt, inkl renter, avdrag, gebur, ekstra innbetaling på lån og ekstra kjøp.
- asset.transferedAmount - Hva du har overført til/fra denne asset. Kan være både positivt og negativt beløp.  (fra transfer, source eller rule). Ikke changerate.
- asset.mortageRateDecimal- Hvor mye i % av en asset som er lånt. Belåningsgrad. 
- asset.taxableDecimal - Skattbar prosent - Antall prosent av markedsverdien til en asset det skal skattes av
- asset.taxableAmount - Skattbart beløp - Antall kroner av markedsverdien til en asset det skal skattes av
- asset.taxableAmountOverride - Auto: Set to true for all coming years if it finds a asset.taxableAmount the first year.
- asset.taxDecimal - Prosent skatt på asset op en assets skattbare verdi
- asset.taxAmount - Kroner skatt på asset op en assets skattbare verdi
- asset.changerate - Hvor mye en asset endrer seg i verdi pr år
- asset.rule
- asset.transfer
- asset.repeat
- asset.taxablePropertyDecimal -  Skattbar prosent - Antall prosent av markedsverdien til en asset det skal beregnes eiendomsskatt av
- asset.taxablePropertyAmount- Skattbart beløp - Antall kroner av markedsverdien til en asset det skal betales eiendomsskatt av (både % og bunnfradrad hensyntatt)
- asset.taxPropertyAmount - Eiendomsskatt i kroner. Beregnes av asset.marketAmount.
- asset.taxPropertyDecimal - Eiendomsskatt i prosent
- asset.realizationAmount - Beløpet man sitter igjen med etter et salg = asset.marketAmount - asset.realizationTaxAmount
- asset.realizationTaxableAmount - Skattbart beløp ved realisering av asset = asset.marketAmount - asset.acquisitionAmount
- asset.realizationTaxAmount - Skattbart beløp ved realisering av asset = asset.realizationTaxableAmount * asset.realizationTaxDecimal
- asset.realizationTaxDecimal - Skattbar prosent ved realisering av asset. Lest fra tax.json
- asset.description - Beskrivelse av asset/liability

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
"assets": {
"income": {
"meta": {
"type": "income",
"group": "private",
"name": "Income",
"description": "Income",
"active": true,
"tax": "income"
},
"income": {
"2023": {
"name": "Income",
"description": "Income",
"asset": 40000,
"transferRule": "add&5%",
"transferResource": "otp.$year.asset.amount",
"changerate": "changerates.kpi",
"repeat": true
},
"$pensionWishYear": {
"name": "Income",
"description": "Pensioned, no more income from here",
"asset": "=0",
"changerate": "changerates.zero",
"repeat": false
}
},
"expence": {
"2023": {
"name": "Expences",
"description": "",
"asset": 15000,
"changerate": "changerates.kpi",
"repeat": true
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
"asset": {
"2037": {
"name": "inheritance",
"description": "",
"asset": 1000000,
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
"tax": "income"
},
"income": {
"$pensionOfficialYear": {
"name": "Folketrygden",
"description": "Folketrygden fra $pensionOfficialYear",
"amount": 15000,
"changerate": "changerates.kpi",
"repeat": true
}
}
},
"otp": {
"meta" : {
"type": "fond",
"group": "private",
"name": "OTP",
"description": "OTP",
"active": true,
"tax": "fond"
},
"asset": {
"2023": {
"amount": "=500000",
"changerate": "changerates.otp",
"description": "OTP Sparing frem til pensjonsår",
"repeat": true
},
"$otpStartYear": {
"transferRule": "transfer&1/$otpYears",
"transferResource": "income.$year.income.amount",
"changerate": "changerates.otp",
"description": "OTP fra $otpStartYear, -1/$otpYears av formuen fra pensjonsåret",
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
"tax": "house"
},
"asset": {
"2023": {
"amount": "3000000",
"changerate": "changerates.house",
"description": "",
"repeat": true
}
},
"expence": {
"2023": {
"name": "Utgifter",
"description": "Kommunale/Forsikring/Strøm/Eiendomsskatt 7300 mnd",
"amount": 7300,
"changerate": "changerates.kpi",
"repeat": true
}
},
"mortgage": {
"2023": {
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
"type": "fond",
"group": "private",
"name": "fond privat",
"description": "",
"active": true,
"tax": "fond"
},
"asset": {
"2022": {
"amount": 2000000,
"changerate": "changerates.fond",
"description": "Første innskudd på 2 millioner",
"repeat": true
},
"2023": {
"name": "Monthly savings",
"description": "Setter inn 6000,- pr år",
"amount": "+6000",
"repeat": true
},
"2033": {
"name": "Monthly savings",
"description": "Slutter å sette inn 6000,- pr år",
"amount": "+0",
"repeat": true
},
"$pensionWishYear": {
"transferRule": "transfer&1/$pensionWishYears",
"transferResource": "income.$year.income.amount",
"changerate": "changerates.fond",
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
"asset": {
"2022": {
"amount": 50000,
"changerate": "changerates.cash",
"description": "Kontanter p.t.",
"repeat": true
},
"$pensionWishYear": {
"transferRule": "transfer&1/$pensionWishYears",
"transferResource": "income.$year.income.amount",
"changerate": "changerates.fond",
"description": "Uttak fra $pensionWishYear, -1/$pensionWishYears",
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
"asset": {
"2020": {
"amount": 50000,
"changerate": "changerates.car",
"description": "verditap",
"repeat": true
}
},
"expence": {
"2019": {
"name": "Utgifter",
"description": "Drivstoff/Forsikring/Vedlikehold 4000,- pr mnd (med høy dieselpris)",
"amount": 3000,
"changerate": "changerates.kpi",
"repeat": true
}
}
}
}
}


Algoritme for å beregne ny årlig betaling med ekstra avdrag:

Start med det opprinnelige lånebeløpet
�
L.
Beregn den årlige betalingen
�
P for lånet uten ekstra avdrag ved hjelp av annuitetsformelen.
Hvert år, trekk fra den ekstra betalingen fra lånebeløpet før du beregner renten.
Trekk den årlige betalingen
�
P fra den nye lånesummen.
Gjenta trinn 3 og 4 inntil lånebeløpet er 0 eller negativt.
Eksempel:

Gitt:

�
L (lånebeløpet) = 1,000,000
�
r (den årlige rentesatsen i desimalform) = 6% = 0.06
�
n (antall år) = 20
�
≈
87
,
247.01
P≈87,247.01 (som vi beregnet tidligere)
Ekstra årlig betaling = 10,000
La oss bruke algoritmen:

Start med
�
L = 1,000,000.
Første års rente =
�
×
�
L×r = 1,000,000 × 0.06 = 60,000.
Betal ned lånet med
�
P + 10,000 = 97,247.01.
Nytt lånebeløp = 1,000,000 + 60,000 - 97,247.01 = 962,752.99.
Gjenta trinn 2-4 for det nye lånebeløpet.
Ved å følge denne prosessen vil du se at lånet blir betalt ned raskere enn 20 år.

For å finne ut nøyaktig hvor mye raskere du vil bli ferdig med lånet ved å betale 10,000 ekstra hvert år, vil det være mest effektivt å lage en iterativ beregning (for eksempel i et regneark eller ved hjelp av en programmeringsskript).

Men generelt, ved å betale ekstra ned på hovedstolen hvert år, reduserer du mengden renter som akkumuleres, og dermed reduserer du den totale tiden det tar å betale tilbake lånet.
