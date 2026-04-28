---
template: features
title: "Features — Wealth Prognosis"
description: "A complete feature overview: year-by-year prognosis, taxation, FIRE metrics, AI assistant, multi-tenancy, mortgage modelling and more."
og_type: website

hero:
  badge: "Features"
  title_html: |
    Everything Wealth Prognosis does,<br>in one place.
  lead: "A complete planning platform covering prognosis, taxation, FIRE, AI, mortgages and more — built for long-term financial clarity."

groups_label: "Feature groups"

groups:
  - title: "Prognosis engine"
    intro: "The calculation core simulates every year between today and your expected death year."
    items:
      - { name: "Year-by-year simulation", body: "Income, expenses, mortgages, cash flow, taxes and asset value computed per year for every asset you own." }
      - { name: "Three scenarios", body: "Run the same configuration as pessimistic, realistic and optimistic — change rates configurable per asset." }
      - { name: "Transfers between assets", body: "Move cash flow or asset value from one asset to another with correct taxation on realization." }
      - { name: "Rule-based additions", body: "Add fixed amounts, percentages of other assets, or derived values (e.g. 5% of salary to OTP)." }
      - { name: "Repeat &amp; milestone years", body: "Use $pensionWishYear, $deathYear, $pensionOfficialYear as symbolic years that adapt to your life plan." }
      - { name: "Excel export", body: "Export the full prognosis to Excel with per-asset sheets, type sheets and a totals sheet." }

  - title: "Taxation"
    intro: "Complete coverage of Norwegian tax rules, year by year. Swedish and Swiss tax calculations are available in beta."
    items:
      - { name: "Fortune tax (formueskatt)", body: "Calculated per year based on aggregated net wealth, with tiered rates from the tax configuration." }
      - { name: "Property tax (eiendomsskatt)", body: "Per-municipality rates, including the tax-free threshold and bunnfradrag." }
      - { name: "Income &amp; capital tax", body: "Salary, capital gains, interest and dividend taxation with correct brackets and deductions." }
      - { name: "Rental &amp; company tax", body: "Rental income, company income tax and dividend tax on distributions to private." }
      - { name: "Tax shield (skjermingsfradrag)", body: "Correct shielding of dividends and capital gains against the base rate." }
      - { name: "Realization &amp; transfer tax", body: "Correct taxation when realizing assets inside a company before transferring to private." }
      - { name: "Sweden &amp; Switzerland (beta)", body: "Core Swedish taxation (kapitalinkomst, ISK/KF schablonskatt, kapitalvinst, statlig &amp; kommunal inkomstskatt) and Swiss taxation (federal + cantonal income, wealth tax, Säule 3a) are usable today in beta." }

  - title: "Mortgages &amp; loans"
    intro: "Full mortgage modelling with tax-deductible interest."
    items:
      - { name: "Annuity mortgages", body: "Term amount, interest, principal, gebyr and balance computed per year for the life of the loan." }
      - { name: "Extra downpayments", body: "Transfer cash flow to the mortgage to reduce principal — the engine recalculates years remaining." }
      - { name: "Tax-deductible interest", body: "Deductible amount, rate and percent tracked per year and fed back into income tax." }
      - { name: "Max mortgage calculation", body: "See how much you can borrow based on income, existing debt and property value." }

  - title: "F.I.R.E metrics"
    intro: "Not the 4% rule — a sell-down strategy that keeps your essential assets."
    items:
      - { name: "Financial independence year", body: "See exactly when your liquid assets can cover your remaining lifetime expenses." }
      - { name: "Sell-down simulation", body: "Liquidate liquid assets to zero over your retirement span — with tax on each realization." }
      - { name: "Essential assets kept", body: "Your house, cabin, car and boat stay with you; the simulation only sells what you mark as liquid." }

  - title: "AI assistant"
    intro: "Natural-language access to your entire financial configuration."
    items:
      - { name: "English &amp; Norwegian", body: "&quot;Legg til en tesla til verdi 200K med lån 100K over 7 år&quot; or &quot;Set my house value to 3.5M NOK&quot; — both just work." }
      - { name: "Powered by Gemini", body: "Google Gemini under the hood; conversation history is stored per user so the assistant has context between questions." }
      - { name: "Tool-calling", body: "The AI can create assets, update mortgages, adjust values and explain tax computations through safe, scoped tools." }
      - { name: "What-if analysis", body: "Ask &quot;what if I pay down the mortgage by 10K/month?&quot; and get an explained comparison." }

  - title: "Multi-tenancy &amp; security"
    intro: "Your data belongs to your team and nobody else."
    items:
      - { name: "Team-scoped data", body: "A global scope filters every query by team_id — your assets never appear in another user's dashboard." }
      - { name: "Audit stamping", body: "created_by, updated_by, created_checksum and updated_checksum on every model, maintained automatically." }
      - { name: "Signed download URLs", body: "Analysis files are served through signed, auth-protected routes." }
      - { name: "Modern admin", body: "Fast admin UI with resource tables, forms, filters and bulk actions." }

  - title: "Assets &amp; configuration"
    intro: "15+ asset types covering everything you can own."
    items:
      - { name: "Real estate", body: "Houses, cabins, rental properties with property tax, maintenance and rental income." }
      - { name: "Vehicles", body: "Cars, boats, motorcycles with depreciation, insurance and running costs." }
      - { name: "Pensions", body: "OTP, IPS, folketrygd and private pensions with correct tax treatment." }
      - { name: "Financial assets", body: "Stocks, bonds, mutual funds, ETFs, crypto, cash — each with its own tax profile." }
      - { name: "Companies", body: "AS/ASA company ownership with dividend, income and transfer taxation." }
      - { name: "Loans &amp; liabilities", body: "Mortgages, student loans, credit lines — all modelled as negative assets." }

closing:
  heading: "Ready to see your future?"
  lead: "Open the admin dashboard, configure your first asset, and run the simulation."
  cta_primary: "Open dashboard"
  cta_secondary: "Back to home"

schema:
  feature_list:
    - { name: "Prognosis engine", description: "Year-by-year simulation of income, expenses, mortgages, cash flow, taxes and asset value across pessimistic, realistic and optimistic scenarios." }
    - { name: "Taxation", description: "Complete coverage of fortune tax, property tax, income tax, capital tax, rental tax, company tax, dividend tax and the tax shield. Swedish and Swiss tax calculations are available in beta." }
    - { name: "Mortgages and loans", description: "Annuity mortgages, extra downpayments, tax-deductible interest and max-mortgage calculation." }
    - { name: "F.I.R.E metrics", description: "Sell-down strategy for liquid assets while keeping essential assets such as house, cabin, car and boat." }
    - { name: "AI assistant", description: "Natural-language English and Norwegian access to the full configuration via Google Gemini with tool-calling." }
    - { name: "Multi-tenancy and security", description: "Team-scoped data, audit stamping, signed download URLs and a modern admin UI." }
    - { name: "Assets and configuration", description: "15+ asset types covering real estate, vehicles, pensions, financial assets, companies and liabilities." }
---
