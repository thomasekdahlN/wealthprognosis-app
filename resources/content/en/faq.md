---
template: faq
title: "FAQ — Wealth Prognosis"
description: "Answers to the most common questions about Wealth Prognosis — how the simulation works, what taxes are supported, how the AI assistant behaves, privacy, pricing and more."
og_type: website

hero:
  badge: "Frequently asked"
  title_html: |
    Questions,<br>answered.
  lead: "Everything people usually want to know about Wealth Prognosis — the engine, the taxes, the AI and your data."

groups:
  - title: "The basics"
    items:
      - q: "What is Wealth Prognosis?"
        a: |
          <p>A financial planning and simulation system that tracks every asset you own, applies accurate taxation, and simulates your economy year by year — from today until your expected death year.</p><p class="mt-3">You can run pessimistic, realistic and optimistic scenarios side by side, see exactly when you can retire, and ask an AI assistant to explain or adjust your configuration in plain language.</p>
      - q: "Who is it for?"
        a: |
          <p>Long-term thinkers who want clarity about their finances over decades, not just this month. It is especially strong for people planning for early retirement (FIRE), optimising taxation, or modelling how a mortgage strategy plays out over 20–30 years.</p>
      - q: "Do I need to be a developer to use it?"
        a: |
          <p>No. Everything is managed through the admin dashboard — adding assets, running simulations, exporting to Excel. The command line and JSON configs are there if you want them, but not required.</p>
      - q: "How much does it cost?"
        a: |
          <p>Hosted plans start at 79 NOK / month for a single user, with tiers for households, advisors, businesses and enterprises. Every hosted plan includes a 30-day trial. See the <a href="/en/pricing" class="text-brand-300 hover:text-brand-200 underline underline-offset-2">pricing page</a> for details.</p>

  - title: "Simulation &amp; calculations"
    items:
      - q: "How far into the future does the simulation go?"
        a: |
          <p>From today until the death year you configure. Every year in between is computed individually — income, expenses, mortgages, taxes, asset value and cash flow.</p>
      - q: "What is the difference between pessimistic, realistic and optimistic?"
        a: |
          <p>Each scenario uses its own change-rate for every asset (e.g. stock growth, real-estate appreciation, inflation). The same configuration produces three parallel projections so you can see a range instead of a single optimistic number.</p>
      - q: "How accurate are the FIRE numbers?"
        a: |
          <p>More accurate than a pure "4% rule" calculation, because the engine does a real sell-down simulation: your liquid assets are liquidated down to zero across your retirement span, and each realisation is taxed correctly. Essentials you mark as non-liquid (house, cabin, car, boat) are kept.</p>
      - q: "Can I model extra mortgage downpayments?"
        a: |
          <p>Yes. You can transfer cash flow from one asset into a mortgage — the engine recomputes the remaining years and interest automatically, and feeds the reduced tax-deductible interest back into income tax.</p>

  - title: "Taxation"
    items:
      - q: "Which Norwegian taxes are supported?"
        a: |
          <p>Fortune tax (<em>formueskatt</em>), property tax (<em>eiendomsskatt</em>), income tax, capital-gains tax, pension tax, rental tax, company tax, dividend tax and the tax shield (<em>skjermingsfradrag</em>). All are computed per year, per asset, with correct brackets.</p>
      - q: "What about other countries?"
        a: |
          <p>Norway is the fully-supported default today. <strong class="text-white">Sweden and Switzerland are available in beta</strong> — the core brackets, wealth tax and capital gains rules are modelled and usable, but some edge cases (municipal variations, cantonal differences, pension specifics) are still being refined. Feedback from beta users shapes what ships next.</p><p class="mt-3">The tax engine is configuration-driven, so additional jurisdictions can be added. Enterprise customers can request custom tax configurations.</p>
      - q: "Does it handle company-to-private transfers correctly?"
        a: |
          <p>Yes — the engine realises company-held assets before distribution and applies the right layers of tax (company tax, then dividend tax on the amount transferred to private).</p>

  - title: "AI assistant"
    items:
      - q: "What can I ask the AI?"
        a: |
          <p>Anything about your configuration. "Legg til en Tesla til verdi 200K med lån 100K over 7 år", "set my house value to 3.5M NOK", or "what if I pay down the mortgage by 10K per month for three years?" — the assistant can both explain numbers and change your configuration through safe, scoped tools.</p>
      - q: "Which AI model is used?"
        a: |
          <p>Google Gemini. Conversation history is stored per user so the assistant has context between questions.</p>
      - q: "Does the AI ever see other users' data?"
        a: |
          <p>No. Every AI tool runs through the same team-scoped queries as the rest of the app, so the assistant can only see and modify the assets that belong to the signed-in team.</p>

  - title: "Privacy &amp; hosting"
    items:
      - q: "Where is my data stored?"
        a: |
          <p>Hosted plans run on secure EU infrastructure with encrypted backups. You can export everything to JSON or Excel at any time — your data belongs to you.</p>
      - q: "Is the data multi-tenant?"
        a: |
          <p>Yes. A global scope filters every query by <code class="text-brand-300">team_id</code>, and every model has audit stamping (<code class="text-brand-300">created_by</code>, <code class="text-brand-300">updated_by</code>, checksums). Your data never mixes with another team's.</p>
      - q: "Can I export everything?"
        a: |
          <p>Yes. Full Excel export of the prognosis, per-asset sheets, per-type sheets and a totals sheet. You own your data.</p>

  - title: "Getting started"
    items:
      - q: "How do I try it?"
        a: |
          <p>Open the <a href="/admin" class="text-brand-300 hover:text-brand-200 underline">dashboard</a>, sign in, and add your first asset. The <a href="/en/features" class="text-brand-300 hover:text-brand-200 underline">features page</a> walks through everything the engine can do.</p>

closing:
  heading: "Still have questions?"
  lead: "Try the dashboard and see for yourself, or get in touch with the team."
  cta_primary: "Open dashboard"
  cta_secondary: "See pricing"
---
