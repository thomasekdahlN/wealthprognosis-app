##Run

Predicts your economic future and wealth taking into consideration all known parameters, and calculates it for yearly as long as you wish

Note: This is just a hack and is not production ready.

How to run
php artisan ReadFile2 yourassetconfig.json realistic/positive/negative all/private/company

php artisan ReadFile2 tests/Feature/config/example.json realistic private

tests/Feature/config/example.json = path to your asset definition.
realistic/positive/negative are standard prognosis. You can copy and make your own, just place them in the same directory.
all/private/comapny - run the prognosis for only, private, only company or both.

Output:
The command will automatically generate excel files in the same directory as your config file, with the same name as the run config file.

Reads your economic setup as a json file and provides a detail spreadsheet with analysis of your future economy until your death.

Supports % changerates on expences, income and asset values
Supports mortage calculations and new mortages taking over for a previous mortage
Support incrementell decrease of income / expence / asset (using negative amount as input)
Supports calculation of fortune tax in norway (but not adding it to expences)
Calculates all values for all assets
Groups assets into groups for group overview
Groups assets into total, company and private for a total overview of your economic future
Estimates your max loan capasity from banks.
- Calculate 4% retirement on asset cash flow (FIRE)
- FIRE income = income + 4%asset value + deductable taxes + loan principal
- FIRE expence = expence + mortgage + taxable taxes
- FIRE cashflow = FIRE income - FIRE expence amount
- FIRE % = FIRE income / FIRE expence = How close you are to fire
- FIRE SavingRate = FIRE cashflow / FIRE income (in progress)

- Support for more sophisticated dynamics in income/expence/asset - 
-- "1000" - Value is set to 1000.
-- "+10%" - Adds 10% to value (Supported now, but syntax : 10)
-- "+1000" - Adds 1000 to value
-- "-10%" - Subtracts 10% from value
-- "-1000" - Subtracts 1000 from value (Supported now - same syntax)
-- =+1/10" - Adds 1 tenth of the amount yearly
-- =-1/10" - Subtracts 1 tenth of the amount yearly (To simulate i.e OTP payment). The rest amount will be zero after 10 years. Lile 1/10 first year, 1/9 next year, 1/8 the year after and the last year 1/1.

ToDo
- KPI arkfane og beregning (må ikke summeres)
- 
- Positve eiendeler
- Negative eiendeler
- Klassifisere FIRE oppnåelse pr år
- Prognose splittes i tre_ negativ, positiv, realistisk (genere 3 xls filer?). Prognose må settes pr år.
- Arkfane som grupperer assets i verdi pr asset gruppe pr år (for å se spredning av assets)
- Likvidering og skatt må beregnes mer riktig.
- Skatteoppsettet må være pr år.

- fond - innskudd akkumuleres ikke i formuen og fire blir feil om man setter det inn fra inntekt. Usikker på om det egentlig er en bug.
- Beregning av skattbar formue og formuesskatt - trukket fra cash flow.
- FIRE uttak beregnes fra wishPenison year inn i inntekt på person. Skatteberegnes også?
- Er OTP uttak skattbart? Sjekk og juster.
- Graf som viser formuen (fratrukket gjeld) i % fordelt på ulike grupper assets. Eiendom, fond, krypto, råvarer, aksjer, kontanter, pensjon
- Realisert verdi etter skatt

On the wishlist:
- - Showing all values compared to KPI index (relative value) and how we perform compared to kpi

- Mortage calculation supporting extra downpayments each year (Need help here)
- Configurable transfer of cash flow in % between assets (and mortages)
- Support incremental amount addition of expences/income (not only %)
- Showing % increase on assets, income and expences in asset spreadsheet
- Central changerates configured pr year to make curve predictions
- Retrieving asset values from API, like Crypto/Fond/stocks
- FIRE asset usage is not deducted from the asset value, it probably should.
- Fortune tax is not added to expences for the year calculated (but methods are ready now)
- Refactoring and cleanup of code.

{
"meta": {
"name": "John Doe",
"birthyear": "1975",
"prognoseyear": "2032"
},
"period": {
"start": 2005,
"end": 2055
},
"assets": {
"house": {
"meta": {
"type": "house",
"group": "private",
"name": "My house",
"description": "Here I live",
"active": true,
"tax": "house"
},
"value": {
"2015": {
"value": 3000000,
"changerate": "changerates.house",
"description": "Bought the house",
"repeat": true
}
},
"income": {
"2015": {
"name": "Rental",
"description": "Rental 10000 pr month",
"value": 10000,
"changerate": "changerates.kpi",
"repeat": true
}
},
"expence": {
"2010": {
"name": "Utgifter",
"description": "Kommunale/Forsikring/Strøm/Eiendomsskatt 7300 mnd",
"value": 7300,
"changerate": "changerates.kpi",
"repeat": true
}
},
"mortgage": {
"2015": {
"value": 1500000,
"interest": "changerates.interest",
"gebyr": 600,
"tax": 22,
"paymentExtra": "home.$year.cashflow.amount",
"years": 20
}
}
},
"crypto": {
"meta": {
"type": "crypto",
"group": "private",
"name": "My Crypto",
"description": "Coinbase private",
"active": true,
"tax": "private"
},
"value": {
"2022": {
"value": 5000,
"changerate": "changerates.crypto",
"description": "",
"repeat": true
}
}
},
"nordnet": {
"meta": {
"type": "fond",
"group": "private",
"name": "Nordnet Privat",
"description": "",
"active": true,
"tax": "private"
},
"value": {
"2022": {
"value": 50000,
"changerate": "changerates.fond",
"description": "",
"repeat": true
}
}
},
"cash": {
"meta": {
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
"meta": {
"type": "car",
"group": "private",
"name": "Avensis",
"description": "",
"active": true,
"tax": "wealth"
},
"value": {
"2020": {
"value": 50000,
"changerate": -5,
"description": "5% verditap",
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
},
"boat": {
"meta": {
"type": "boat",
"group": "private",
"name": "Boat",
"description": "",
"active": true,
"tax": "wealth"
},
"value": {
"2022": {
"value": 200000,
"changerate": -5,
"description": "5% verditap",
"repeat": true
}
},
"expence": {
"2022": {
"name": "Utgifter",
"description": "Drivstoff/Forsikring/Vedlikehold/Opplag",
"value": 2000,
"changerate": "changerates.kpi",
"repeat": true
}
}
},
"klp": {
"meta": {
"type": "fond",
"group": "company",
"name": "KLP fond",
"description": "",
"active": true,
"tax": "company"
},
"value": {
"2022": {
"description": "",
"value": 1000000,
"changerate": "changerates.fond",
"repeat": true
}
}
},
"Company AS": {
"meta": {
"type": "stock",
"group": "company",
"name": "Company AS",
"description": "I own stocks here",
"active": true,
"tax": "company"
},
"value": {
"2022": {
"value": 2500000,
"changerate": 3.5,
"description": "Initielt kjøp",
"repeat": true
}
}
},
"salary": {
"meta": {
"type": "salary",
"group": "private",
"name": "Salary",
"description": "My Salary",
"active": true,
"tax": "salary"
},
"income": {
"2022": {
"name": "Salary",
"description": "My monthly salary",
"value": 35000,
"changerate": "changerates.kpi",
"repeat": true
}
},
"expence": {
"2022": {
"name": "Expences",
"description": "My monthly expences - not including house, car and boat",
"value": 10000,
"changerate": "changerates.kpi",
"repeat": true
}
}
}
}
}
