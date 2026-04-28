---
template: use-cases
title: "Use cases — who Wealth Prognosis is for"
description: "Concrete use cases for Wealth Prognosis: planning F.I.R.E, managing property portfolios, running a one-person AS, comparing early vs normal retirement, modelling inheritance and company-to-private transfers."
og_type: article

hero:
  badge: "Use cases"
  title_html: |
    One engine,<br>many financial lives.
  lead: "Whether you are chasing early retirement, running a property portfolio, or extracting value from a one-person AS — Wealth Prognosis models the full picture, year by year, after tax."

labels:
  problem: "The problem"
  how: "How Wealth Prognosis handles it"
  outcome: "Outcome"

cases:
  - slug: "fire"
    badge: "F.I.R.E"
    title: "Planning for financial independence"
    audience: "Employees and self-employed people aiming to retire years before the official pension age."
    problem: "A 4% rule spreadsheet gives you a single number. It does not account for Norwegian fortune tax, capital-gains tax on fund sales, bracket-tax effects on pension income, or the fact that your house, cabin and car are not part of the sell-down."
    how:
      - "Configure birth year, wished retirement year, official pension year and expected death year."
      - "Add every asset with realistic change rates — equity funds, ASK, pension, bank, property."
      - "Mark liquid vs. non-liquid. The engine liquidates liquid assets evenly from retirement to death."
      - "Run the three-scenario simulation. See whether the pessimistic scenario still clears expenses."
      - "Ask the AI: \"hvor mye må jeg spare i aksjefondet per måned for å kunne gå av ved 55?\""
    outcome: "A year-by-year view of net worth, cash flow, tax and FIRE progress under three market regimes — not just a single optimistic headline number."

  - slug: "property"
    badge: "Property investor"
    title: "Running a property portfolio"
    audience: "Private landlords and property investors with primary home plus one or more rentals."
    problem: "Tracking net yield after municipal property tax, fortune tax on real-estate value, rental tax, deductible interest and eventual capital-gains tax on sale is tedious — and changes every year the mortgage amortises."
    how:
      - "Add each property as its own asset with market value, mortgage, rental income and maintenance."
      - "Apply the correct municipal property-tax configuration (327 Norwegian municipalities ship with the app)."
      - "Let the engine compute annuity amortisation, deductible interest and rental-tax per year."
      - "Simulate selling a rental in a future year — the engine applies realisation tax and transfers the net into another asset."
      - "Compare keeping vs. selling across pessimistic, realistic and optimistic scenarios."
    outcome: "Clear visibility into whether each property earns its keep on an after-tax basis, and a defensible plan for when to sell or refinance."

  - slug: "one-person-as"
    badge: "One-person AS"
    title: "Extracting value from a limited company"
    audience: "Consultants and founders running a Norwegian AS who need to plan salary vs. dividend vs. retained earnings."
    problem: "You can pay yourself salary (taxed as income), dividend (company tax, then dividend tax on the net, with a shielding deduction), or build up retained earnings. The trade-offs compound over decades."
    how:
      - "Model the company as a separate asset group with its own cash flow and fortune-tax valuation."
      - "Add salary rules that transfer from company to private with correct income-tax brackets."
      - "Add dividend rules — the engine applies 22% company tax first, then dividend tax on the net above the tax shield."
      - "Simulate a \"take over as private\" event in a future year and see the full realisation-plus-dividend stack."
      - "Compare strategies side by side: all-salary, all-dividend, mixed with retained earnings."
    outcome: "A 20-year projection showing which extraction strategy leaves you with the most after-tax wealth — not just this year but every year."

  - slug: "retirement-timing"
    badge: "Retirement timing"
    title: "Early, normal or delayed retirement"
    audience: "Anyone within ten years of retirement wondering which year actually makes the most sense."
    problem: "Folketrygden, AFP, tjenestepensjon and private savings all kick in on different dates and are taxed differently. Small changes in when you start each of them can move lifetime net worth by six figures."
    how:
      - "Set three different wished retirement years and run three parallel configurations."
      - "Let the engine sequence public pension, OTP and private savings automatically based on configured start years."
      - "Watch cash-flow and net-worth curves for each scenario on the same axis."
      - "Spot the year where delayed retirement stops being worth it — usually when health or time become the binding constraint."
    outcome: "A direct, numeric answer to the question \"how much does it cost me to retire three years earlier?\""

  - slug: "inheritance"
    badge: "Household planning"
    title: "Child costs, barnetrygd and inheritance"
    audience: "Households with children at home or a known future inheritance event."
    problem: "Kids are negative cash flow until they move out; then they are not. Inheritance lands in a future year with its own tax treatment. Both events distort long-term plans if modelled as a flat average."
    how:
      - "Add each child as an asset with income (barnetrygd), expenses, and a \"removed from economy\" year."
      - "Model an inheritance event in a future year with the expected value and tax treatment."
      - "Let the engine compute before/after cash-flow and net-worth changes automatically."
      - "Scenario-test what happens if the inheritance is delayed or reduced."
    outcome: "An honest picture of your economy through and beyond the child-raising years — and a plan that does not fall over when the timeline shifts."

  - slug: "advisors"
    badge: "Advisors"
    title: "Advising multiple clients"
    audience: "Independent financial advisors, accountants and family-office operators."
    problem: "Each client has a different portfolio, tax situation and timeline. Keeping spreadsheets per client is fragile and slow to update when tax rules change."
    how:
      - "One team per client — data is fully isolated via multi-tenant team scoping."
      - "Shared change-rate configurations so assumptions are consistent across your book."
      - "Export the year-by-year Excel to send to the client after each meeting."
      - "Use the AI assistant in Norwegian or English to make quick configuration tweaks live."
    outcome: "A single system to maintain your assumptions, run every client's simulation in minutes, and deliver a professional exportable document."

closing:
  heading: "Your case isn't on this list?"
  lead: "The engine is configuration-driven. If you can describe an asset, income stream or tax event, you can model it."
  cta_primary: "Try it with your own data"
  cta_secondary: "Read the FAQ"

schema:
  name: "Wealth Prognosis use cases"
---
