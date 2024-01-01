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
-- "1000" - Value is set to 1000.
-- "+10%" - Adds 10% to value (Supported now, but syntax : 10)
-- "+1000" - Adds 1000 to value
-- "-10%" - Subtracts 10% from value
-- "-1000" - Subtracts 1000 from value (Supported now - same syntax)
-- =+1/10" - Adds 1 tenth of the amount yearly
-- =-1/10" - Subtracts 1 tenth of the amount yearly (To simulate i.e OTP payment). The rest amount will be zero after 10 years. Lile 1/10 first year, 1/9 next year, 1/8 the year after and the last year 1/1.

### Supported assets for prognosis and tax calculation
* boat
* car
* cash
* child
* company
* crypto
* fond
* inheritance
* kpi
* otp
* pension
* property
* rental
* salary

### On the wishlist:
- Support for skjermingsfradrag
- Support for correct taxt calculation on sale and part sale or transfer of assets
- Support for extra downpayments for loans, also reducing the length of the loan
- Fortune tax - check that it is correct
- F.I.R.E - Use up percentage of partly sellable assets from wishPensionYear to DeathYear
- Showing all values compared to KPI index (relative value) and how we perform compared to kpi

- FIRE uttak beregnes fra wishPenison year inn i inntekt på person. Skatteberegnes også?
- Er OTP uttak skattbart? Sjekk og juster.
- Graf som viser formuen (fratrukket gjeld) i % fordelt på ulike grupper assets. Eiendom, fond, krypto, råvarer, aksjer, kontanter, pensjon
- F.I.R.E Sparerate
- FIRE asset usage is not deducted from the asset value, it probably should.
- Klassifisere F.I.R.E oppnåelse pr år
- Tax configuration pr year and for different countries
- Refactoring and cleanup of code - its ugly as hell.
- Retrieving asset values from API, like Crypto/Fond/stocks

Gjøre beregningene pr år så asset, ikke asset pr år som nå (da vil ikke verdiøkning o.l være med)

Beregningstyper

- [ ] Initiell verdi - kan erstattes av rule
- [ ] Value change i % / prognosis
- [ ] Rule. En av reglene som tilfører eller fjerner verdi av en asset uten å påvirke andre assets,
- [ ] Transfer - flytter penger mellom assets. Reduserer verdien på asset det flyttes fra, øker på den det flyttes til. Samme regneregler som rule ellers.
- [ ] Calculate - beregner en verdi som skal legges til en asset basert på en annen asset eller en annen type beregning. Er det egentlig en rule?

Ikke lagre prev verdier i variabler, men i dataH med oppslag på year - 1. Vil huske alt hele tiden, state.
Lagre rules og transfers og calculations i dataH. Må man lagre mer repeterende ting.

Regnerekkefølge: calclulate, transfer, rule, value change

Hvis en transfer blir gjort, må alt regnes på nytt på både fra og til asset siden verdi er påvirket, En transfer må også gjøre skatteberegninger som en realisasjon.

Summere riktige verdier på hva man faktisk har gitt for en assset,
Summere riktige verdier for hva man har gitt minus det man tar ut - for riktig skatteberegning (separat felt)
Summere riktig skattefradrag basert på rente og alder.
Riktig skatt på realisasjon

Hvordan skal skatten struktureres? Sammen med typene eller i egen skatteestruktur

Changerate - changepercent
Changerule
Transferrule
Calculationrule

### Datasettet vi regner på pr år

#### Income
- income.amount - beløp før skatt
- income.changerate - endring i prosent
- income.rule - regler for hvordan inntekten skal behandles
- income.transfer - overføring av inntekt til en annen asset
- income.repeat - gjenta konfigurasjonen for kommende år
- income.description - beskrivelse av inntekten

#### Expence
- expence.amount - beløp før skatt
- expence.changerate - endring i prosent
- expence.rule - regler for hvordan utgiften skal behandles
- expence.transfer - overføring av inntekt til en annen asset
- expence.repeat - gjenta konfigurasjonen for kommende år
- expence.description - beskrivelse av utgiften

#### Cashflow
- cashflow.beforeTaxAmount = income.amount - expence.amount
- cashflow.afterTaxAmount = cashflow.beforeTaxAmount - cashflow.taxYearlyAmount
- cashflow.beforeTaxAggregatedAmount += cashflow.beforeTaxAccumulatedAmount
- cashflow.afterTaxAggregatedAmount += cashflow.afterTaxAccumulatedAmount
- cashflow.taxAmount - skatt beløp (could be positive or negative, deponds on income positive opr negative)
- cashflow.taxDecimal - skatt prosent
- cashflow.description - beskrivelse av cashflow

#### mortgage - Lån
#- mortgage.amount - Nedbetaling av lån pr år ihht betingelsene (renter + avdrag + gebyr) = interestAmount + principalAmount + gebyrAmount (NOT USED)
- mortgage.termAmount - Nedbetaling av lån pr år ihht betingelsene (renter + avdrag + gebyr) = interestAmount + principalAmount + gebyrAmount
- mortgage.interestAmount - renter - i kroner pr år
- mortgage.principalAmount - Avdrag - i kroner pr år (det er dette som nedbetaler lånet)
- mortgage.balanceAmount - gjenstående lån i kroner
- mortgage.extraDownpaymentAmount - ekstra nedbetaling av lån pr år
- mortgage.interestDecimal - rente i prosent
- mortgage.gebyrAmount - gebyr pr år
- mortgage.taxDeductableAmount - fradrag
- mortgage.taxDeductableDecimal - fradrag i prosent

- mortgage.description - beskrivelse av lånet

#### asset
- asset.marketAmount - Markedsverdien på en asset
- asset.marketMortgageDeductedAmount - Markedsverdien ved salg hensyntatt restlån men ikke skatt : asset.amount - mortgage.balanceAmount 
- asset.acquisitionAmount - Anskaffelsesverdi. Vi trenger å vite denne for å skatteberegne ved realisasjon, da det ofte trekkes fra før skatt. F.eks verdi på hus ved kjøp.
- asset.equityAmount - Egenkapital : asset.acquisitionAmount - mortgage.balanceAmount (hensyntar da automatisk ekstra nedbetalign av lån). Legger også til ekstra overføringer fra rule eller transfer regler som egenkapital.
- asset.paidAmount - Hva du faktisk har betalt, inkl renter, avdrag, gebur, ekstra innbetaling på lån og ekstra kjøp.
- asset.mortageRateDecimal- Hvor mye av en asset som er lånt
- asset.taxableDecimal - Skattbar prosent - Antall prosent av markedsverdien til en asset det skal skattes av
- asset.taxableAmount - Skattbart beløp - Antall kroner av markedsverdien til en asset det skal skattes av
- asset.taxDecimal - Prosent skatt på asset op en assets skattbare verdi
- asset.taxAmount - Kroner skatt på asset op en assets skattbare verdi
- asset.changerate - Hvor mye en asset endrer seg i verdi pr år
- asset.rule
- asset.transfer
- asset.repeat
- asset.realizationTaxableAmount - Skattbart beløp ved realisering av asset = asset.amount - asset.originalAmount
- asset.realizationTaxAmount - Skattbart beløp ved realisering av asset
- asset.realizationTaxDecimal - Skattbar prosent ved realisering av asset
- asset.realizationTaxDeductableAmount - Fradrag ved realisering av asset
- asset.realizationTaxDeductableDecimal - Fradrag prosent ved realisering av asset
- asset.description - Beskrivelse av asset/liability

#### Potential
How much potential the bank sees in your income - expences
- potential.incomeAmount
- potential.mortgageAmount - Hvor mye du potensielt kan låne. debtCapacity?


#### fire (F.I.R.E) - beregnes på income, expence, asset, mortgage, cashflow
Før eller etter skatt her?
- fire.decimal - F.I.R.E inntekt / F.I.R.E utgift - prosent
- fire.incomeAmount - F.I.R.E inntekt
- fire.expenceAmount - F.I.R.E utgift
- fire.diffDecimal - F.I.R.E inntekt / F.I.R.E utgift . prosent
- fire.cashFlowAmount - F.I.R.E inntekt - F.I.R.E utgift
- fire.savingAmount - sparebeløp. Hvor mye du kan spare pr år. Ikke medregnet nedbdetalign av lån. Bare frie assets ala fond, krypto, aksjer, kontanter
- fire.savingRate - savingAmount / income    


### Example config

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
    "salary": {
    "meta": {
    "type": "salary",
    "group": "private",
    "name": "Salary",
    "description": "Salary",
    "active": true,
    "tax": "salary"
    },
    "income": {
    "2022": {
    "name": "Salary",
    "description": "Salary",
    "value": 40000,
    "changerate": "changerates.kpi",
    "repeat": true
    },
    "$pensionWishYear": {
    "name": "Salary",
    "description": "Pensioned, no more salary from here",
    "value": "=0",
    "changerate": "changerates.zero",
    "repeat": false
    }
    },
    "expence": {
    "2022": {
    "name": "Expences",
    "description": "",
    "value": 15000,
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
    "value": {
    "2037": {
    "name": "inheritance",
    "description": "",
    "value": 1000000,
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
    "tax": "salary"
    },
    "income": {
    "$pensionOfficialYear": {
    "name": "Folketrygden",
    "description": "Folketrygden fra $pensionOfficialYear",
    "value": 15000,
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
    "value": {
    "2022": {
    "value": "=500000",
    "changerate": "changerates.otp",
    "transfer": "salary.$year.income.amount*0.05",
    "description": "OTP Sparing frem til pensjonsår",
    "repeat": true
    },
    "$otpStartYear": {
    "value": "-1/$otpYears",
    "transfer": "salary.$year.income.amount=diff",
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
    "value": {
    "2023": {
    "value": "-1/3",
    "changerate": "changerates.house",
    "description": "Selling part of the house",
    "repeat": true
    }
    },
    "expence": {
    "2023": {
    "name": "Utgifter",
    "description": "Kommunale/Forsikring/Strøm/Eiendomsskatt 7300 mnd",
    "value": 7300,
    "changerate": "changerates.kpi",
    "repeat": true
    }
    },
    "mortgage": {
    "2023": {
    "value": 1500000,
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
    "value": {
    "2022": {
    "value": 2000000,
    "changerate": "changerates.fond",
    "description": "",
    "repeat": true
    },
    "2023": {
    "name": "Monthly savings",
    "description": "Setter inn 6000,- pr år",
    "value": "+6000",
    "repeat": true
    },
    "2033": {
    "name": "Monthly savings",
    "description": "Slutter å sette inn 6000,- pr år",
    "value": "+0",
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
    "value": {
    "2022": {
    "value": 50000,
    "changerate": "changerates.cash",
    "description": "Kontanter p.t.",
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
    "value": {
    "2020": {
    "value": 50000,
    "changerate": "changerates.car",
    "description": "verditap",
    "repeat": true
    }
    },
    "expence": {
    "2019": {
    "name": "Utgifter",
    "description": "Drivstoff/Forsikring/Vedlikehold 4000,- pr mnd (med høy dieselpris)",
    "value": 3000,
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
