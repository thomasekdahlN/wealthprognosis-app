# 📊 Wealth Prognosis - Dashboards & Widgets

## 📋 Table of Contents
- [Dashboard Overview](#dashboard-overview)
- [Actual Assets Dashboard](#actual-assets-dashboard)
- [Simulation Dashboard](#simulation-dashboard)
- [Simulation Detailed Reporting Dashboard](#simulation-detailed-reporting-dashboard)
- [Compare Dashboard](#compare-dashboard)
- [Widget Calculation Reference](#widget-calculation-reference)

---

## 🎯 Dashboard Overview

The Wealth Prognosis system provides four specialized dashboards, each serving a distinct purpose in financial planning and analysis.

---

## 📈 Actual Assets Dashboard

**Purpose:** Display current financial status based on actual asset data for the current year.

**URL:** `/admin/config/{configuration}/dashboard`

**Example:** `http://wealthprognosis-app.test/admin/config/88/dashboard`

**Why It's Useful:**
- Quick snapshot of current financial health
- Real-time FIRE progress tracking
- Identifies areas for improvement
- Monitors monthly cashflow and savings

### Widgets

#### 1. **Asset Overview Widget** 
`App\Filament\Widgets\Configuration\ConfigurationAssetOverviewWidget`

**What It Does:** Displays high-level asset totals and net worth.

**Why It's Useful:** Provides instant visibility into total wealth and debt levels.

**Calculations:**
```json
{
  "total_assets": "SUM(asset_years.asset_market_amount) WHERE year = current_year AND asset.is_active = true",
  "investment_assets": "SUM(asset_years.asset_market_amount) WHERE year = current_year AND asset_types.is_liquid = true",
  "net_worth": "total_assets - total_liabilities",
  "total_mortgage": "SUM(asset_years.mortgage_amount) WHERE year = current_year"
}
```

**Metrics Displayed:**
- **Total Assets** - All assets combined
- **Investment Assets** - FIRE-sellable (liquid) assets only
- **Net Worth** - Assets minus liabilities
- **Total Mortgage** - Current mortgage balance

---

#### 2. **Monthly Cashflow Widget**
`App\Filament\Widgets\Configuration\ConfigurationMonthlyCashflowWidget`

**What It Does:** Shows monthly and annual income, expenses, and cashflow.

**Why It's Useful:** Identifies spending patterns and savings capacity.

**Calculations:**
```json
{
  "monthly_income": "SUM(income_amount) WHERE income_factor = 'monthly' + SUM(income_amount / 12) WHERE income_factor = 'yearly'",
  "monthly_expenses": "SUM(expence_amount) WHERE expence_factor = 'monthly' + SUM(expence_amount / 12) WHERE expence_factor = 'yearly'",
  "monthly_cashflow": "monthly_income - monthly_expenses",
  "annual_income": "monthly_income * 12",
  "annual_expenses": "monthly_expenses * 12",
  "expense_ratio": "(annual_expenses / annual_income) * 100"
}
```

**Metrics Displayed:**
- **Monthly Income** - Total monthly income (with annual equivalent)
- **Monthly Expenses** - Total monthly expenses (with annual equivalent)
- **Monthly Cashflow** - Net monthly cashflow (income - expenses)
- **Expense Ratio** - Expenses as percentage of income

---

#### 3. **FIRE Analysis Widget**
`App\Filament\Widgets\Configuration\ConfigurationFireAnalysisWidget`

**What It Does:** Analyzes Financial Independence Retire Early (FIRE) metrics.

**Why It's Useful:** Shows progress toward financial independence and retirement readiness.

**Calculations:**
```json
{
  "fire_number": "annual_expenses * 25",
  "fire_progress": "(investment_assets / fire_number) * 100",
  "fire_achieved": "investment_assets >= fire_number",
  "safe_withdrawal_rate": "(annual_expenses / investment_assets) * 100",
  "annual_passive_income": "investment_assets * 0.04",
  "expense_coverage": "(annual_passive_income / annual_expenses) * 100"
}
```

**Metrics Displayed:**
- **FIRE Number** - 25x annual expenses (4% rule)
- **Current Progress** - Percentage toward FIRE goal
- **FIRE Achievement** - Whether FIRE is achieved and when
- **Safe Withdrawal Rate** - Current withdrawal rate percentage
- **Annual Passive Income** - 4% of FIRE portfolio
- **Expense Coverage** - Passive income vs expenses percentage

---

#### 4. **FIRE Progress Widget**
`App\Filament\Widgets\Configuration\ConfigurationFireProgressWidget`

**What It Does:** Visual progress bar showing FIRE completion percentage.

**Why It's Useful:** Motivational visual indicator of FIRE journey progress.

**Calculations:**
```json
{
  "progress_percentage": "MIN(100, (investment_assets / fire_number) * 100)"
}
```

---

#### 5. **FIRE Crossover Widget**
`App\Filament\Widgets\Configuration\ConfigurationFireCrossoverWidget`

**What It Does:** Shows when passive income exceeds expenses (crossover point).

**Why It's Useful:** Identifies the point of true financial independence.

**Calculations:**
```json
{
  "passive_income": "investment_assets * 0.04",
  "crossover_achieved": "passive_income >= annual_expenses",
  "shortfall": "annual_expenses - passive_income"
}
```

---

#### 6. **Net Worth Over Time Widget**
`App\Filament\Widgets\Configuration\ConfigurationNetWorthOverTimeWidget`

**What It Does:** Line chart showing net worth progression over years.

**Why It's Useful:** Visualizes wealth accumulation trends.

**Calculations:**
```json
{
  "net_worth_by_year": "SUM(asset_market_amount) - SUM(mortgage_amount) GROUP BY year"
}
```

---

#### 7. **Cash Flow Over Time Widget**
`App\Filament\Widgets\Configuration\ConfigurationCashFlowOverTimeWidget`

**What It Does:** Line chart showing income vs expenses over time.

**Why It's Useful:** Identifies income/expense trends and savings patterns.

**Calculations:**
```json
{
  "income_by_year": "SUM(income_amount * income_factor_multiplier) GROUP BY year",
  "expenses_by_year": "SUM(expence_amount * expence_factor_multiplier) GROUP BY year",
  "cashflow_by_year": "income_by_year - expenses_by_year"
}
```

---

#### 8. **Asset Allocation Widgets**

**By Type:** `ConfigurationAssetAllocationByTypeWidget`
**By Category:** `ConfigurationAssetAllocationByCategoryWidget`
**By Tax Type:** `ConfigurationAssetAllocationByTaxTypeWidget`

**What They Do:** Donut charts showing asset distribution.

**Why They're Useful:** Understand portfolio diversification and risk exposure.

**Calculations:**
```json
{
  "allocation_by_type": "SUM(asset_market_amount) GROUP BY asset_type",
  "allocation_by_category": "SUM(asset_market_amount) GROUP BY asset_category",
  "allocation_by_tax_type": "SUM(asset_market_amount) GROUP BY tax_type"
}
```

---

#### 9. **Expense Breakdown Widget**
`App\Filament\Widgets\Configuration\ConfigurationExpenseBreakdownWidget`

**What It Does:** Donut chart showing expense categories.

**Why It's Useful:** Identifies major expense categories for optimization.

**Calculations:**
```json
{
  "expenses_by_category": "SUM(expence_amount * expence_factor_multiplier) GROUP BY asset_category"
}
```

---

#### 10. **Tax Analysis Widget**
`App\Filament\Widgets\Configuration\ConfigurationTaxAnalysisWidget`

**What It Does:** Shows tax burden by type and effective tax rate.

**Why It's Useful:** Identifies tax optimization opportunities.

**Calculations:**
```json
{
  "total_tax": "SUM(tax_income + tax_fortune + tax_property + tax_realization)",
  "effective_tax_rate": "(total_tax / gross_income) * 100",
  "tax_by_type": "SUM(tax_amount) GROUP BY tax_type"
}
```

---

#### 11. **Retirement Readiness Widget**
`App\Filament\Widgets\Configuration\ConfigurationRetirementReadinessWidget`

**What It Does:** Assesses readiness for retirement based on age and assets.

**Why It's Useful:** Helps plan retirement timing and savings goals.

**Calculations:**
```json
{
  "current_age": "current_year - birth_year",
  "years_to_retirement": "pension_wish_age - current_age",
  "retirement_readiness_score": "(investment_assets / fire_number) * 100",
  "projected_retirement_income": "investment_assets * 0.04"
}
```

---

## 🔮 Simulation Dashboard

**Purpose:** Display results of a financial simulation with projections over time.

**URL:** `/admin/config/{configuration}/sim/{simulation}/dashboard`

**Example:** `http://wealthprognosis-app.test/admin/config/88/sim/55/dashboard`

**Why It's Useful:**
- Visualize long-term financial projections
- Test different scenarios (realistic, optimistic, pessimistic)
- Identify future milestones (FIRE achievement, debt-free year)
- Plan for retirement and major life events

### Widgets

#### 1. **Simulation Key Figures Widget**
`App\Filament\Widgets\Simulation\SimulationKeyFiguresWidget`

**What It Does:** Displays KPI cards for net worth, assets, debt, cashflow, FIRE%, and LTV.

**Why It's Useful:** Quick overview of simulation outcomes.

**Calculations:**
```json
{
  "net_worth": "SUM(asset_market_amount) - SUM(mortgage_balance_amount)",
  "total_assets": "SUM(asset_market_amount)",
  "total_debt": "SUM(mortgage_balance_amount)",
  "annual_cashflow": "SUM(cashflow_after_tax_amount)",
  "fire_percent": "AVG(fire_percent)",
  "ltv_percent": "AVG(metrics_ltv_percent)"
}
```

**Metrics Displayed:**
- **Total Net Worth** - Current and final year comparison
- **Total Assets** - Asset value progression
- **Total Debt** - Debt reduction over time
- **Annual Cash Flow** - Net cashflow after taxes
- **FIRE Progress %** - Average FIRE completion
- **LTV %** - Loan-to-value ratio

---

#### 2. **Simulation Milestones Widget**
`App\Filament\Widgets\Simulation\SimulationMilestonesWidget`

**What It Does:** Shows key milestone years (FIRE achieved, debt-free, net worth milestones).

**Why It's Useful:** Identifies important financial turning points.

**Calculations:**
```json
{
  "fire_achieved_year": "MIN(year) WHERE fire_percent >= 100",
  "debt_free_year": "MIN(year) WHERE mortgage_balance_amount = 0",
  "net_worth_million_year": "MIN(year) WHERE net_worth >= 1000000",
  "passive_income_exceeds_expenses_year": "MIN(year) WHERE (asset_market_amount * 0.04) >= expence_amount"
}
```

**Metrics Displayed:**
- **FIRE Achieved** - Year when FIRE goal is reached
- **Debt Free** - Year when all debt is paid off
- **Net Worth Milestone** - Year reaching 1M, 5M, 10M NOK
- **Passive Income Crossover** - Year passive income exceeds expenses

---

#### 3. **Simulation Net Worth Growth Widget**
`App\Filament\Widgets\Simulation\SimulationNetWorthGrowthWidget`

**What It Does:** Stacked area chart showing equity, debt, and total assets over time.

**Why It's Useful:** Visualizes wealth accumulation and debt reduction trajectory.

**Calculations:**
```json
{
  "equity_by_year": "SUM(asset_equity_amount) GROUP BY year",
  "debt_by_year": "SUM(mortgage_balance_amount) GROUP BY year",
  "assets_by_year": "SUM(asset_market_amount) GROUP BY year"
}
```

---

#### 4. **Simulation Income vs Expenses Widget**
`App\Filament\Widgets\Simulation\SimulationIncomeVsExpensesWidget`

**What It Does:** Dual-line chart comparing income and expenses over time.

**Why It's Useful:** Shows if income keeps pace with expenses and identifies gaps.

**Calculations:**
```json
{
  "income_by_year": "SUM(income_amount) GROUP BY year",
  "expenses_by_year": "SUM(expence_amount) GROUP BY year"
}
```

---

#### 5. **Simulation Annual Cash Flow Widget**
`App\Filament\Widgets\Simulation\SimulationAnnualCashFlowWidget`

**What It Does:** Bar chart showing net cashflow after taxes by year.

**Why It's Useful:** Identifies years with positive/negative cashflow.

**Calculations:**
```json
{
  "cashflow_by_year": "SUM(cashflow_after_tax_amount) GROUP BY year"
}
```

---

#### 6. **Simulation FIRE Progression Widget**
`App\Filament\Widgets\Simulation\SimulationFireProgressionWidget`

**What It Does:** Line chart showing FIRE percentage progress over time.

**Why It's Useful:** Visualizes journey to financial independence.

**Calculations:**
```json
{
  "fire_percent_by_year": "AVG(fire_percent) GROUP BY year"
}
```

---

#### 7. **Simulation Asset Allocation Widget**
`App\Filament\Widgets\Simulation\SimulationAssetAllocationWidget`

**What It Does:** Donut chart showing asset distribution by type.

**Why It's Useful:** Shows portfolio composition in the simulation.

**Calculations:**
```json
{
  "allocation": "SUM(asset_market_amount) GROUP BY asset_type FOR current_year"
}
```

---

#### 8. **Simulation Debt Allocation Widget**
`App\Filament\Widgets\Simulation\SimulationDebtAllocationWidget`

**What It Does:** Donut chart showing debt distribution by asset.

**Why It's Useful:** Identifies which assets carry the most debt.

**Calculations:**
```json
{
  "debt_allocation": "SUM(mortgage_balance_amount) GROUP BY asset FOR current_year"
}
```

---

## 📑 Simulation Detailed Reporting Dashboard

**Purpose:** Provide in-depth analysis and drill-down capabilities for simulation data.

**URL:** `/admin/config/{configuration}/sim/{simulation}/detailed-reporting`

**Example:** `http://wealthprognosis-app.test/admin/config/88/sim/55/detailed-reporting`

**Why It's Useful:**
- Deep dive into specific assets
- Analyze income, expense, and tax details
- Identify optimization opportunities
- Export detailed reports

### Widgets

#### 1. **Simulation Asset Drill-Down Table Widget**
`App\Filament\Widgets\Simulation\SimulationAssetDrillDownTableWidget`

**What It Does:** Interactive table showing all assets with year-by-year data.

**Why It's Useful:** Detailed asset-level analysis with filtering and sorting.

---

#### 2. **Simulation Income Report Widget**
`App\Filament\Widgets\Simulation\SimulationIncomeReportWidget`

**What It Does:** Detailed breakdown of income sources over time.

**Why It's Useful:** Identifies income trends and diversification.

**Calculations:**
```json
{
  "income_by_source": "SUM(income_amount) GROUP BY asset, year"
}
```

---

#### 3. **Simulation Expense Report Widget**
`App\Filament\Widgets\Simulation\SimulationExpenseReportWidget`

**What It Does:** Detailed breakdown of expenses by category over time.

**Why It's Useful:** Identifies expense trends and cost-cutting opportunities.

**Calculations:**
```json
{
  "expenses_by_category": "SUM(expence_amount) GROUP BY asset_category, year"
}
```

---

#### 4. **Simulation Tax Report Widget**
`App\Filament\Widgets\Simulation\SimulationTaxReportWidget`

**What It Does:** Comprehensive tax analysis by type and year.

**Why It's Useful:** Tax planning and optimization.

**Calculations:**
```json
{
  "tax_income": "SUM(tax_income_amount) GROUP BY year",
  "tax_fortune": "SUM(tax_fortune_amount) GROUP BY year",
  "tax_property": "SUM(tax_property_amount) GROUP BY year",
  "tax_realization": "SUM(tax_realization_amount) GROUP BY year",
  "total_tax": "tax_income + tax_fortune + tax_property + tax_realization"
}
```

---

#### 5. **Simulation Financial Metrics Heatmap Widget**
`App\Filament\Widgets\Simulation\SimulationFinancialMetricsHeatmapWidget`

**What It Does:** Heatmap visualization of key metrics over time.

**Why It's Useful:** Quickly identify patterns and anomalies.

**Metrics:**
- Savings rate
- ROI (Return on Investment)
- LTV (Loan-to-Value)
- Debt-to-income ratio

---

## 🔄 Compare Dashboard

**Purpose:** Side-by-side comparison of two simulation scenarios.

**URL:** `/admin/config/{configuration}/compare?simulationA={id}&simulationB={id}`

**Example:** `http://wealthprognosis-app.test/admin/config/88/compare?simulationA=55&simulationB=56`

**Why It's Useful:**
- Evaluate impact of different decisions
- Compare optimistic vs pessimistic scenarios
- Assess risk vs reward trade-offs
- Make data-driven financial decisions

### Widgets

#### 1. **Compare Scenario Assumptions Widget**
`App\Filament\Widgets\Compare\CompareScenarioAssumptionsWidget`

**What It Does:** Lists key assumptions and differences between scenarios.

**Why It's Useful:** Understand what changed between scenarios.

---

#### 2. **Compare Key Outcomes Widget**
`App\Filament\Widgets\Compare\CompareKeyOutcomesWidget`

**What It Does:** Side-by-side comparison of final outcomes.

**Why It's Useful:** Quick comparison of end results.

**Metrics:**
- Final net worth
- FIRE achievement year
- Total income/expenses
- Tax burden

---

#### 3. **Compare Net Worth Trajectory Widget**
`App\Filament\Widgets\Compare\CompareNetWorthTrajectoryWidget`

**What It Does:** Dual-line chart comparing net worth over time.

**Why It's Useful:** Visualizes divergence between scenarios.

---

#### 4. **Compare Cash Flow Trajectory Widget**
`App\Filament\Widgets\Compare\CompareCashFlowTrajectoryWidget`

**What It Does:** Dual-line chart comparing cashflow over time.

**Why It's Useful:** Shows impact on annual cashflow.

---

#### 5. **Compare Delta Chart Widget**
`App\Filament\Widgets\Compare\CompareDeltaChartWidget`

**What It Does:** Bar chart showing year-by-year difference (B - A).

**Why It's Useful:** Highlights when and how much scenarios diverge.

**Calculations:**
```json
{
  "delta_by_year": "net_worth_B - net_worth_A GROUP BY year"
}
```

---

#### 6. **Compare Debt Load Widget**
`App\Filament\Widgets\Compare\CompareDebtLoadWidget`

**What It Does:** Compares debt levels between scenarios.

**Why It's Useful:** Assess debt management strategies.

---

#### 7. **Compare Risk Metrics Widget**
`App\Filament\Widgets\Compare\CompareRiskMetricsWidget`

**What It Does:** Compares risk indicators (volatility, drawdowns).

**Why It's Useful:** Evaluate risk exposure differences.

---

#### 8. **Compare AI Analysis Widget**
`App\Filament\Widgets\Compare\CompareAiAnalysisWidget`

**What It Does:** AI-generated insights on scenario differences.

**Why It's Useful:** Get intelligent recommendations on which scenario is better.

---

## 📐 Widget Calculation Reference

### Common Formulas

#### FIRE Number
```
FIRE Number = Annual Expenses × 25
```
Based on the 4% safe withdrawal rate rule.

#### FIRE Progress
```
FIRE Progress % = (Investment Assets / FIRE Number) × 100
```

#### Safe Withdrawal Rate
```
Safe Withdrawal Rate % = (Annual Expenses / Investment Assets) × 100
```

#### Net Worth
```
Net Worth = Total Assets - Total Liabilities
```

#### Expense Ratio
```
Expense Ratio % = (Annual Expenses / Annual Income) × 100
```

#### LTV (Loan-to-Value)
```
LTV % = (Mortgage Balance / Asset Market Value) × 100
```

#### Savings Rate
```
Savings Rate % = ((Annual Income - Annual Expenses) / Annual Income) × 100
```

---

*Last Updated: 2025-11-03*

