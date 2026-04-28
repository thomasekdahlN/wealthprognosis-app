---
template: methodology
title: "Methodology — how every advanced widget is calculated"
description: "The exact mathematics behind every advanced widget in Wealth Prognosis — FIRE progress, crossover point, safe withdrawal rate, retirement readiness, savings rate, net worth projection and taxation — written as LaTeX formulas with worked examples."
og_type: article

hero:
  badge: "Methodology"
  title_html: |
    The math behind<br>every number.
  lead: "No black boxes. Every advanced widget in Wealth Prognosis is documented here with its exact formula, inputs and a worked example — so you can trust the output, audit it, and reproduce it."
  cta_primary: "Jump to the formulas"
  cta_secondary: "Read the glossary"

labels:
  inputs: "Inputs"
  caveats: "Caveats"
  formula: "Formula"
  example: "Worked example"

pillars:
  - title: "Three scenarios, always"
    body: "Every formula runs three times — pessimistic, realistic, optimistic — using different change rates. You see the full range of outcomes side by side."
  - title: "Year-by-year, not averages"
    body: "Taxes, expenses and returns are computed for each year individually. No smoothing, no single headline number hiding the truth."
  - title: "Auditable by design"
    body: "Every calculated row carries a SHA-256 checksum plus created/updated stamps. You can export the full workings to Excel or JSON at any time."

widgets_section:
  heading: "Advanced widgets"
  lead: "The widgets that do real mathematics — not just sums and group-bys. Each one lists its data inputs, the exact formula, a worked example, and the caveats we want you to know."

widgets:
  - slug: "net-worth"
    title: "Net worth over time"
    lede: "The headline number. Market value of everything you own, minus everything you owe, charted year by year."
    inputs:
      - "asset_market_amount (per asset, per year)"
      - "mortgage_amount (per asset, per year)"
    formula: "\\[ NW_y \\;=\\; \\sum_{a \\in A_y} M_{a,y} \\;-\\; \\sum_{a \\in A_y} L_{a,y} \\]"
    legend: "\\(NW_y\\) net worth in year \\(y\\), \\(M_{a,y}\\) market value of asset \\(a\\), \\(L_{a,y}\\) mortgage balance, \\(A_y\\) all active assets for the chosen configuration."
    example: "Year 2024 — assets total 8 450 000, mortgages total 3 200 000. \\(NW_{2024} = 8\\,450\\,000 - 3\\,200\\,000 = 5\\,250\\,000\\)."
    caveats: "Uses your actual entered values up to the current year; future years come from the prognosis engine, not this widget."

  - slug: "fire-number"
    title: "FIRE number &amp; progress"
    lede: "How much wealth you need to retire — and how far you already are. The single most important number on the dashboard."
    inputs:
      - "expence_amount (all assets, current year)"
      - "asset_market_amount (liquid + preserved, current year)"
    formula: "\\[ F \\;=\\; 25 \\times E \\qquad\\quad p \\;=\\; \\min\\!\\left(\\tfrac{P}{F},\\, 1\\right) \\]"
    legend: "\\(F\\) FIRE number, \\(E\\) annual expenses, \\(P\\) current portfolio value, \\(p\\) progress toward FIRE (0 to 1)."
    example: "Annual expenses 540 000, portfolio 7 200 000. \\(F = 25 \\times 540\\,000 = 13\\,500\\,000\\); \\(p = 7\\,200\\,000 / 13\\,500\\,000 \\approx 53.3\\%\\)."
    caveats: "The 25× multiplier is the inverse of the 4% safe-withdrawal rule. For a more conservative plan, raise the multiplier (30× ⇒ 3.33% SWR)."

  - slug: "fire-crossover"
    title: "FIRE crossover point"
    lede: "The moment your portfolio can pay for your life from passive income alone. After this you are, in principle, free."
    inputs:
      - "asset_market_amount (current year)"
      - "expence_amount (current year)"
    formula: "\\[ \\text{crossover} \\;\\Longleftrightarrow\\; 0.04 \\cdot P \\;\\geq\\; E \\]"
    legend: "\\(P\\) current portfolio, \\(E\\) annual expenses. The 0.04 constant is the classic 4% safe-withdrawal rate."
    example: "Portfolio 15 000 000, expenses 540 000. Passive income \\(0.04 \\times 15\\,000\\,000 = 600\\,000 \\geq 540\\,000\\) ⇒ crossover achieved."
    caveats: "Binary indicator — not a sell-down simulation. For year-by-year withdrawal feasibility across three scenarios, the full prognosis engine runs a real liquidation against your actual assets and taxes."

  - slug: "fire-metrics"
    title: "FIRE metrics over 30 years"
    lede: "Projects net worth forward 30 years against an inflation-adjusted FIRE target, so you can see the year the two lines cross."
    inputs:
      - "current net worth"
      - "annual savings (\\(I - E\\))"
      - "growth rate \\(r = 7\\%\\)"
      - "inflation \\(\\pi = 3\\%\\)"
    formula: "\\[ P_{t+1} \\;=\\; (P_t + S)(1 + r), \\qquad F_t \\;=\\; F_0 \\cdot (1 + \\pi)^t \\]"
    legend: "\\(P_t\\) projected portfolio in year \\(t\\), \\(S\\) annual savings (income minus expenses), \\(r\\) nominal growth rate, \\(F_t\\) FIRE number inflated from \\(F_0\\) by \\(\\pi\\)."
    example: "Start \\(P_0 = 5\\,000\\,000\\), \\(S = 300\\,000\\). After one year \\(P_1 = (5\\,000\\,000 + 300\\,000) \\times 1.07 = 5\\,671\\,000\\). After ten years \\(P_{10} \\approx 14\\,020\\,000\\)."
    caveats: "Uses constant \\(r\\) and \\(\\pi\\) for readability. The main prognosis engine runs the same projection per asset, per year, across three scenarios with configurable change rates."

  - slug: "savings-rate"
    title: "Savings rate over time"
    lede: "The single best predictor of your FIRE timeline. A 50% savings rate brings financial independence in roughly 17 years regardless of income."
    inputs:
      - "income_amount (per year, income assets)"
      - "expence_amount (per year, all assets)"
    formula: "\\[ s_y \\;=\\; \\frac{I_y - E_y}{I_y} \\]"
    legend: "\\(s_y\\) savings rate in year \\(y\\), \\(I_y\\) total income, \\(E_y\\) total expenses. Expressed as a percentage. Benchmark line drawn at 20%."
    example: "Income 900 000, expenses 540 000. \\(s = (900\\,000 - 540\\,000) / 900\\,000 = 40\\%\\)."
    caveats: "Historic years only — the widget never projects into the future. Negative when expenses exceed income (drawing down)."

  - slug: "retirement-readiness"
    title: "Retirement readiness"
    lede: "Projects today's net worth to your planned retirement age against a capital-adequacy target, using your own expense baseline."
    inputs:
      - "current net worth"
      - "birth_year, pension_wish_year, death_year"
      - "annual income, annual expenses"
    formula: "\\[ T \\;=\\; 25 \\times 0.80 \\times E \\qquad\\quad NW_{t} \\;=\\; (NW_{t-1} + S)(1+r) \\]"
    legend: "\\(T\\) retirement target (25× of 80% of current expenses — the classic 70–80% income-replacement rule), \\(NW_t\\) projected net worth at age \\(t\\), \\(r\\) assumed growth (default 7%)."
    example: "Expenses 540 000 ⇒ \\(T = 25 \\times 0.80 \\times 540\\,000 = 10\\,800\\,000\\). Starting from 3 000 000 at age 40 with 300 000 annual savings, \\(NW_{65} \\approx 23\\,100\\,000\\) — comfortably above target."
    caveats: "The 80% replacement ratio is a widely used rule of thumb, not a personal forecast. Pension payouts from tjenestepensjon/IPS/offentlig pensjon are modelled separately by the tax engine."

  - slug: "actual-tax-rate"
    title: "Actual effective tax rate"
    lede: "Your real tax burden — every tax the engine calculated, divided by taxable base. Not a headline rate, the rate you actually pay."
    inputs:
      - "income_tax"
      - "fortune_tax"
      - "property_tax"
      - "capital_gains_tax"
      - "taxable_income_base"
    formula: "\\[ \\tau_y \\;=\\; \\frac{T^{\\text{income}}_y + T^{\\text{fortune}}_y + T^{\\text{property}}_y + T^{\\text{gains}}_y}{B_y} \\]"
    legend: "\\(\\tau_y\\) effective tax rate in year \\(y\\), \\(T^{\\star}_y\\) the tax paid of each kind, \\(B_y\\) the taxable base (gross income + realised gains)."
    example: "Gross base 950 000, total taxes 278 400. \\(\\tau = 278\\,400 / 950\\,000 \\approx 29.3\\%\\)."
    caveats: "Fortune tax and property tax are wealth-based but are included in the numerator because they are a real cash outflow. The ratio is not directly comparable to a marginal income-tax rate."

taxation_section:
  heading: "Taxation"
  lead: "The tax engine models the real brackets, thresholds and shielding rules — not rough percentages. Rates, bands and municipal rules are loaded per year from the tax configuration tables."

tax_formulas:
  - title: "Fortune tax (formueskatt)"
    formula: "\\[ T^{\\text{fortune}} \\;=\\; \\max\\!\\bigl(0,\\; W_{\\text{net}} - W_{\\text{threshold}}\\bigr) \\cdot \\bigl(r_{\\text{state}} + r_{\\text{muni}}\\bigr) \\]"
    legend: "\\(W_{\\text{net}}\\) valued net wealth (primary residence, shares, business assets each get their own valuation discount), \\(W_{\\text{threshold}}\\) annual threshold, \\(r_{\\text{state}}\\) and \\(r_{\\text{muni}}\\) state and municipal rates."
  - title: "Bracket tax (trinnskatt)"
    formula: "\\[ T^{\\text{bracket}} \\;=\\; \\sum_{k=1}^{K} r_k \\cdot \\max\\!\\bigl(0,\\; \\min(Y, b_{k+1}) - b_k\\bigr) \\]"
    legend: "\\(Y\\) gross ordinary income, \\(b_k\\) lower bound of bracket \\(k\\), \\(r_k\\) marginal rate for that bracket. Brackets are loaded per year from the tax configuration."
  - title: "Tax shield (skjermingsfradrag)"
    formula: "\\[ S_t \\;=\\; C_t \\cdot r^{\\text{skjerm}}_t, \\qquad T^{\\text{dividend}} \\;=\\; \\max\\!\\bigl(0,\\; D_t - S_t\\bigr) \\cdot g \\cdot r^{\\text{cap}} \\]"
    legend: "\\(C_t\\) cost basis, \\(r^{\\text{skjerm}}_t\\) annual risk-free shielding rate, \\(S_t\\) shielding deduction, \\(D_t\\) dividend received, \\(g\\) gross-up factor (currently 1.72), \\(r^{\\text{cap}}\\) capital tax rate."
  - title: "Property tax (eiendomsskatt)"
    formula: "\\[ T^{\\text{property}} \\;=\\; \\max\\!\\bigl(0,\\; V \\cdot d - V_{\\text{threshold}}\\bigr) \\cdot r_{\\text{muni}} \\]"
    legend: "\\(V\\) property market value, \\(d\\) municipal valuation discount (often 0.70), \\(V_{\\text{threshold}}\\) municipal bottom deduction, \\(r_{\\text{muni}}\\) municipal rate. 327 municipalities ship configured."

prognosis_section:
  heading: "Prognosis math"
  lead: "The primitives that roll every asset forward, year after year, across three scenarios."

prognosis:
  - title: "Yearly compound roll-forward"
    formula: "\\[ V_{y+1} \\;=\\; V_y \\cdot \\bigl(1 + c_{y,s}\\bigr) \\;+\\; \\Delta_{y,s} \\]"
    legend: "Each asset evolves year by year. \\(V_y\\) value in year \\(y\\), \\(c_{y,s}\\) percentage change rate for year \\(y\\) under scenario \\(s\\), \\(\\Delta_{y,s}\\) fixed-amount adjustments (top-ups, transfers, rule-engine mutations)."
  - title: "Compound Annual Growth Rate (CAGR)"
    formula: "\\[ \\text{CAGR} \\;=\\; \\left(\\frac{V_{\\text{end}}}{V_{\\text{start}}}\\right)^{\\!1/n} - 1 \\]"
    legend: "Smoothed annualised growth between two points in time. \\(n\\) is the number of years. Used in simulation summaries and the asset overview card."
  - title: "Real vs. nominal return"
    formula: "\\[ r_{\\text{real}} \\;=\\; \\frac{1 + r_{\\text{nominal}}}{1 + \\pi} - 1 \\]"
    legend: "Converts a nominal return into real (inflation-adjusted) return using CPI \\(\\pi\\). The engine shows both; expenses and tax thresholds are inflated using the same \\(\\pi\\)."
  - title: "Annuity mortgage payment"
    formula: "\\[ A \\;=\\; L \\cdot \\frac{r\\,(1+r)^{n}}{(1+r)^{n} - 1} \\]"
    legend: "\\(A\\) annual payment, \\(L\\) remaining loan, \\(r\\) periodic interest rate, \\(n\\) remaining term. Every year splits into interest (tax-deductible) and principal."

closing:
  heading: "Now run the numbers on your own life."
  lead: "Every formula on this page runs live against your own assets, income, taxes and scenarios the moment you sign in. No spreadsheet, no guesswork."
  cta_primary: "Open the dashboard"
  cta_secondary: "Browse features"

schema:
  headline: "Methodology — how every advanced widget is calculated"
  description: "The exact mathematics behind every advanced widget in Wealth Prognosis, with LaTeX formulas and worked examples."
  about:
    - "FIRE number"
    - "Safe withdrawal rate"
    - "Retirement readiness"
    - "Savings rate"
    - "Fortune tax"
    - "Bracket tax"
    - "Tax shield"
    - "CAGR"
---
