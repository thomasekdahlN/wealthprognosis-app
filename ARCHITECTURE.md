# 🏗️ Wealth Prognosis - System Architecture

## 📋 Table of Contents
- [Overview](#overview)
- [Technology Stack](#technology-stack)
- [Multi-Tenancy Architecture](#multi-tenancy-architecture)
- [Data Model](#data-model)
- [Application Structure](#application-structure)
- [Services Layer](#services-layer)
- [Security & Access Control](#security--access-control)

---

## 🎯 Overview

**Wealth Prognosis** is a comprehensive financial planning and simulation system built with Laravel 12 and Filament 4. The system enables users to:

- 📊 Track financial assets across multiple countries and tax systems
- 💰 Manage income, expenses, and mortgages
- 🔮 Run detailed financial simulations with different scenarios
- 📈 Analyze FIRE (Financial Independence, Retire Early) metrics
- 🎯 Compare multiple financial scenarios side-by-side
- 🤖 Get AI-powered financial planning assistance

---

## 🛠️ Technology Stack

### Core Framework
- **Laravel 12** - PHP web application framework
- **PHP 8.4.11** - Server-side programming language
- **Filament 4** - Admin panel and UI framework (Server-Driven UI)

### Frontend
- **Livewire 3** - Dynamic interfaces without JavaScript frameworks
- **Alpine.js** - Lightweight JavaScript framework (included with Livewire)
- **Tailwind CSS** - Utility-first CSS framework
- **Chart.js** - Data visualization via Filament charts

### Testing
- **Pest 4** - Testing framework with browser testing capabilities
- **PHPUnit 12** - Unit testing framework

### Additional Packages
- **Laravel Sanctum 4** - API authentication
- **Laravel Pint 1** - Code style fixer
- **Laravel Sail 1** - Docker development environment

---

## 🏢 Multi-Tenancy Architecture

### Team-Based Isolation

The system implements **strict team-based multi-tenancy** to ensure complete data isolation between users and organizations.

#### Core Concepts

1. **Teams** (`teams` table)
   - Each team has an owner and multiple members
   - Teams can have custom settings and configurations
   - Teams are the primary isolation boundary

2. **Users** (`users` table)
   - Users can belong to multiple teams
   - Each user has a `current_team_id` indicating their active team
   - User-team relationships stored in `team_user` pivot table

3. **Global Scope** (`TeamScope`)
   - Automatically filters all queries by `current_team_id`
   - Applied to all models with `team_id` column
   - Prevents cross-team data access

#### Implementation

```php
// Applied in models
protected static function booted(): void
{
    parent::booted();
    static::addGlobalScope(new TeamScope);
}

// TeamScope filters by current user's team
public function apply(Builder $builder, Model $model): void
{
    $user = Auth::user();
    if ($user && $user->current_team_id) {
        $builder->where($model->getTable().'.team_id', $user->current_team_id);
    }
}
```

#### Team-Scoped Models
All major models include `team_id`:
- `Asset`, `AssetConfiguration`, `AssetType`, `AssetCategory`
- `SimulationConfiguration`, `SimulationAsset`, `SimulationAssetYear`
- `ChangeRateConfiguration`, `PrognosisType`

#### Exceptions (Global Data)
These models are **NOT** team-scoped as they contain reference data:
- `TaxConfiguration` - Tax rules by country/year
- `TaxProperty` - Property tax rates by country/year
- `TaxType` - Tax type definitions

---

## 📊 Data Model

### Core Entities

#### 1. Asset Configuration (`asset_configurations`)
The top-level container for a person's or company's financial profile.

**Key Fields:**
- `name` - Configuration name (e.g., "John's Retirement Plan")
- `birth_year` - Owner's birth year
- `pension_wish_age` - Desired retirement age
- `pension_official_age` - Official retirement age
- `expected_death_age` - Life expectancy for planning
- `risk_tolerance` - Investment risk profile
- `team_id`, `user_id` - Multi-tenancy fields

#### 2. Assets (`assets`)
Individual financial assets owned by the configuration.

**Key Fields:**
- `asset_configuration_id` - Parent configuration
- `name` - Asset name (e.g., "Primary Residence", "Stock Portfolio")
- `asset_type` - Type code (links to `asset_types.type`)
- `group` - Private or Company
- `tax_property` - Tax property code
- `tax_country` - Country code for tax calculations
- `team_id`, `user_id` - Multi-tenancy fields

#### 3. Asset Years (`asset_years`)
Yearly data for each asset (income, expenses, value, mortgage).

**Key Fields:**
- `asset_id` - Parent asset
- `year` - Calendar year
- `income_amount`, `income_factor` - Income (monthly/yearly)
- `expence_amount`, `expence_factor` - Expenses (monthly/yearly)
- `asset_market_amount` - Market value
- `mortgage_amount` - Outstanding mortgage balance

#### 4. Asset Types (`asset_types`)
Defines characteristics and capabilities of different asset types.

**Key Fields:**
- `type` - Unique code (e.g., "house", "stocks", "salary")
- `name` - Display name
- `category` - Asset category
- `is_liquid` - Can be sold quickly (FIRE-sellable)
- `is_private` / `is_company` - Ownership type
- `can_generate_income` - Can produce income
- `can_generate_expenses` - Can have expenses
- `can_have_mortgage` - Can have debt
- `tax_type` - Associated tax type
- `income_changerate`, `expence_changerate`, `asset_changerate` - Growth rates

#### 5. Simulation Configuration (`simulation_configurations`)
Defines a financial simulation scenario.

**Key Fields:**
- `asset_configuration_id` - Source configuration
- `name` - Simulation name
- `prognosis_type` - Scenario (realistic, positive, negative, etc.)
- `group` - Asset scope (private, company, all)
- `tax_country` - Tax system to use
- `birth_year`, `pension_wish_age`, `expected_death_age` - Life parameters

#### 6. Simulation Assets & Years
Copied and calculated data for simulation runs.

- `simulation_assets` - Assets in the simulation
- `simulation_asset_years` - Yearly projections with full calculations

**Calculated Fields in `simulation_asset_years`:**
- Income, expense, cashflow calculations
- Mortgage amortization
- Tax calculations (income, fortune, property, realization)
- FIRE metrics (progress, passive income)
- Financial metrics (LTV, savings rate, ROI)

---

## 🏛️ Application Structure

### Filament Resources (15 total)

Resources provide CRUD interfaces for models:

```
app/Filament/Resources/
├── AssetConfigurations/     # Asset configuration management
├── Assets/                  # Asset management
├── AssetYears/             # Yearly asset data
├── AssetTypes/             # Asset type definitions
├── AssetCategories/        # Asset categories
├── TaxConfigurations/      # Tax rules by country/year
├── TaxTypes/               # Tax type definitions
├── TaxProperty/            # Property tax rates
├── SimulationConfigurations/ # Simulation management
├── ChangeRateConfigurations/ # Growth rate configurations
├── Scenarios/              # Scenario definitions
├── Prognoses/              # Prognosis types
├── Events/                 # Life events
└── AiInstructions/         # AI assistant instructions
```

Each resource follows this structure:
```
ResourceName/
├── ResourceNameResource.php  # Main resource class
├── Pages/                    # List, Create, Edit pages
├── Schemas/                  # Form schemas
├── Tables/                   # Table configurations
└── Actions/                  # Custom actions
```

### Filament Pages

#### Dashboard Pages
- **Dashboard** (`/admin` or `/admin/config/{configuration}/dashboard`)
  - Main dashboard showing actual assets
  - Displays current financial status
  
- **SimulationDashboard** (`/admin/config/{configuration}/sim/{simulation}/dashboard`)
  - Simulation results overview
  - Key metrics and projections
  
- **SimulationDetailedReportingDashboard** (`/admin/config/{configuration}/sim/{simulation}/detailed-reporting`)
  - Detailed simulation analysis
  - Asset drill-down tables
  
- **CompareDashboard** (`/admin/config/{configuration}/compare`)
  - Side-by-side simulation comparison
  - Delta analysis

#### Configuration Pages
- **ConfigAssets** - Asset list for a configuration
- **ConfigAssetYears** - Yearly data for an asset
- **ConfigEvents** - Life events for a configuration
- **ConfigSimulations** - Simulations list

#### Simulation Pages
- **SimulationAssets** - Assets in a simulation
- **SimulationAssetYears** - Yearly data for a simulation asset

---

## ⚙️ Services Layer

### Core Services

#### 1. **FireCalculationService**
Calculates FIRE (Financial Independence, Retire Early) metrics.

**Methods:**
- `getFinancialData()` - Aggregates current financial status
- Calculates: total assets, net worth, income, expenses, FIRE number, progress

#### 2. **PrognosisService**
Runs financial projections and simulations.

**Responsibilities:**
- Year-by-year asset value calculations
- Income and expense projections
- Mortgage amortization
- Tax calculations

#### 3. **PrognosisSimulationService**
Orchestrates complete simulation runs.

**Workflow:**
1. Create simulation configuration
2. Copy assets to simulation tables
3. Run prognosis calculations
4. Store results in `simulation_asset_years`

#### 4. **Tax Services**
Specialized services for tax calculations:
- `TaxIncomeService` - Income tax
- `TaxFortuneService` - Wealth tax
- `TaxPropertyService` - Property tax
- `TaxRealizationService` - Capital gains tax
- `TaxSalaryService` - Salary tax
- `TaxCashflowService` - Cashflow tax calculations

#### 5. **AssetTypeService**
Manages asset type lookups with caching.

**Methods:**
- `getByType()` - Get asset type by code
- `getChangeRate()` - Get growth rate for asset type
- Implements caching for performance

#### 6. **AI Services**
- `AiAssistantService` - AI chat interface
- `AiConfigurationAnalysisService` - Analyzes configurations
- `FinancialPlanningService` - AI-powered planning

#### 7. **Export/Import Services**
- `AssetExportService` - Export to Excel
- `AssetImportService` - Import from JSON
- `SimulationExportService` - Export simulation results

---

## 🔒 Security & Access Control

### Authentication
- Laravel Sanctum for API authentication
- Filament's built-in authentication for admin panel
- Session-based authentication for web interface

### Authorization
- Team-based access control via `TeamScope`
- User must belong to team to access team data
- `current_team_id` determines active team context

### Data Isolation
- All queries automatically filtered by `team_id`
- No cross-team data leakage
- Configuration ID stored in session for context

### Audit Trail
- `Auditable` trait on all models
- Tracks `created_by`, `updated_by`
- Checksums for data integrity (`created_checksum`, `updated_checksum`)

---

## 🔄 Data Flow

### Typical Workflow

1. **User logs in** → Team context established
2. **Select/Create Asset Configuration** → Stored in session
3. **Add Assets** → Linked to configuration and team
4. **Enter Yearly Data** → Income, expenses, values
5. **Run Simulation** → Creates simulation configuration
6. **View Results** → Dashboards and reports
7. **Compare Scenarios** → Side-by-side analysis
8. **Export Data** → Excel reports

### Simulation Flow

```
AssetConfiguration
    ↓
[Create Simulation]
    ↓
SimulationConfiguration
    ↓
[Copy Assets]
    ↓
SimulationAssets
    ↓
[Run Prognosis]
    ↓
SimulationAssetYears (calculated)
    ↓
[Display Results]
    ↓
Dashboards & Reports
```

---

## 📁 Directory Structure

```
app/
├── Filament/
│   ├── Pages/              # Dashboard and custom pages
│   ├── Resources/          # CRUD resources
│   └── Widgets/            # Dashboard widgets
├── Models/                 # Eloquent models
│   ├── Concerns/           # Traits (Auditable)
│   └── Scopes/             # Global scopes (TeamScope)
├── Services/               # Business logic
│   ├── Processing/         # Data processing
│   ├── Prognosis/          # Simulation logic
│   ├── Tax/                # Tax calculations
│   └── Utilities/          # Helper services
├── Exports/                # Excel export classes
└── Providers/              # Service providers

database/
├── migrations/             # Database schema
└── seeders/                # Data seeders

tests/
├── Feature/                # Feature tests
└── Unit/                   # Unit tests
```

---

## 🎨 UI/UX Principles

### Filament 4 Native
- All interfaces built with Filament components
- No custom Blade views (except widget views)
- Server-Driven UI approach

### Responsive Design
- Tables use `maxContentWidth(Width::Full)`
- Mobile-friendly layouts
- Horizontal scrolling for wide tables

### User Experience
- Filters always visible above tables
- Toggleable columns on all tables
- Pagination: 50/100/150 per page (default 50)
- Norwegian number formatting (space separator)
- Intuitive navigation with breadcrumbs
- Pretty URLs (no query parameters)

---

## 🚀 Performance Optimizations

- **Caching**: AssetTypeService caches asset types
- **Eager Loading**: Prevents N+1 queries
- **Indexed Columns**: Database indexes on foreign keys
- **Lazy Loading**: Widgets load data on demand
- **Query Optimization**: Efficient database queries

---

## 📝 Code Standards

- **PSR Compliance**: Code follows PSR standards
- **Larastan Level 9**: Static analysis validation
- **PHPDoc Annotations**: All properties and methods documented
- **Type Hints**: Strict type declarations
- **GPL v3 License**: All model files include copyright header

---

*Last Updated: 2025-11-03*

