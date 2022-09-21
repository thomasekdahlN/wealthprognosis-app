##Run

Predicts your economic future and wealth taking into consideration all known parameters, and calculates it for yearly as long as you wish

How to run
php artisan ReadFile2 example.json example.xlsx

Reads your economic setup as a json file and provides a detail spreadsheet with analysis of your future economy.

Supports % changerates on expences, income and asset values
Supports mortage calculations and new mortages taking over for a previous mortage
Support incrementell decrease of income / expence / asset (using negative amount as input)
Calculates all values for all assets
Groups assets into groups for group overview
Groups assets into total, company and private for a total overview of your economic future
Estimates your max loan capasity from banks.
- Calculate 4% retirement on asset cash flow (FIRE)
- FIRE income = income + 4%asset value + deductable taxes
- FIRE expence = expence + mortgage + taxable taxes
- FIRE diff = FIRE income - FIRE expence amount
- FIRE % = FIRE income / FIRE expence = How close you are to fire 

On the wishlist:
- Mortage calculation supporting extra downpayments each year (Need help here)
- Configurable transfer of cash flow in % between assets (and mortages)
- Support incremental amount addition of expences/income (not only %)
- Showing % increase on assets, income and expences in asset spreadsheet
- Showing all values compared to KPI index (relative value)
- Central changerates configured pr year to make curve predictions
- Retrieving asset values from API, like Crypto/Fond/stocks
- FIRE asset usage is not deducted from the asset value, it probably should.

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
"tax": {
"rental": {
"yearly": 22,
"realization": 22
},
"company": {
"yearly": 0,
"realization": 35.6
},
"salary": {
"yearly": 46,
"realization": 0
},
"house": {
"yearly": 0,
"realization": 22
},
"fond": {
"yearly": 22,
"realization": 35.6
}
},
"changerates": {
"kpi": 3.5,
"crypto": 8,
"fond": 6,
"company": 15,
"cash": 1,
"house": 5,
"interest": 4.5
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
