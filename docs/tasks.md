# Improvement Tasks Checklist

A logically ordered, actionable checklist to improve the architecture, code quality, performance, testing, developer experience, and maintainability of the project. Each task is concrete and can be checked off when completed.

1. [ ] Establish high-level architecture vision and boundaries
   - [ ] Document core domains (Prognosis, Taxation, Changerates, Exporting, Admin UI) and responsibilities in ARCHITECTURE.md
   - [ ] Define service boundaries to reduce god-classes (e.g., split Prognosis responsibilities)
   - [ ] Identify where to apply Value Objects (Money, Year, Percentage, TaxRate)

2. [ ] Refactor App\Models\Prognosis into cohesive services
   - [ ] Extract calculation orchestration into a domain service (e.g., PrognosisEngine)
   - [ ] Extract tax-related logic into dedicated collaborators already present (TaxIncome/TaxFortune/TaxRealization) with clear interfaces
   - [ ] Extract transfer/mortgage logic into dedicated small services (TransferService, MortgageService)
   - [ ] Introduce DTOs for inputs/outputs (AssetMeta, YearlyResult, GroupTotals)
   - [ ] Add unit tests for each extracted service

3. [ ] Make long-running export generation queueable
   - [ ] Create a ShouldQueue job (e.g., GeneratePrognosisExport) that wraps PrognosisExport2
   - [ ] Dispatch job from console command and Filament actions when appropriate
   - [ ] Add progress tracking (model + status columns) and user notifications in Filament
   - [ ] Provide retry/backoff strategy and failure notifications

4. [ ] Improve configuration source for taxation/changerates
   - [ ] Cache JSON configuration reads (config_path("tax/*")) using cache tags with invalidation
   - [ ] Validate configuration shape on load with custom validators (schema validation)
   - [ ] Support per-year config evolution explicitly (typed arrays, accessors)

5. [ ] Introduce Value Objects for financial domains
   - [ ] Money (amount + currency NOK), Percentage, Year, Rate, TaxBase
   - [ ] Replace raw floats/ints across calculations with typed value objects where feasible
   - [ ] Provide serialization helpers for exports and tests

6. [ ] Adopt strict typing and explicit return types across the codebase
   - [ ] Enable declare(strict_types=1) in PHP files where safe
   - [ ] Add parameter and return type hints to public and protected methods
   - [ ] Replace mixed arrays with typed DTOs or array-shape PHPDocs where transition is needed

7. [ ] Strengthen error handling and domain exceptions
   - [ ] Define domain-specific exceptions (InvalidConfigException, CalculationException)
   - [ ] Guard against invalid states and unreachable branches with asserts/exceptions
   - [ ] Add centralized logging context for critical failures with correlation ids

8. [ ] Normalize naming and conventions
   - [ ] Use consistent American English spelling: expense (not expence), prognosis vs. projection naming decision
   - [ ] Ensure method/variable names are descriptive (e.g., isRegisteredForDiscounts style)
   - [ ] Align Filament resource naming to domain (AssetTypeResource already good)

9. [ ] Extract constants and enums
   - [ ] Enums for AssetType, TaxType, PrognosisKind (realistic, positive, negative, zero, variable)
   - [ ] Replace magic strings in switches/conditionals with enums
   - [ ] Add helper mappers and casts for Eloquent

10. [ ] Optimize performance of calculations
    - [ ] Profile core loops in Prognosis calculations to find hotspots
    - [ ] Replace repeated Arr::get/Arr::set deep traversals with prepared structures
    - [ ] Use immutable precomputed lookup tables for yearly rates
    - [ ] Avoid repeated json_decode/file reads by preloading and caching

11. [ ] Improve filesystem interactions and portability
    - [ ] Replace absolute paths in tests (e.g., /tmp) with Storage::fake()/storage_path
    - [ ] Use Laravel filesystem for export writes; provide configurable disk
    - [ ] Add cleanup and retention policy for generated files

12. [ ] Harden ReadFile2 console command
    - [ ] Validate input arguments and file existence with friendly errors
    - [ ] Provide --queue option to dispatch queued export
    - [ ] Respect configurable memory/time limits via config

13. [ ] Enhance Filament admin UX for setup data
    - [ ] Add resources/pages to manage Prognoses (prognosis kinds), ChangeRate configurations
    - [ ] Use Filament Forms relationships for selects and repeaters
    - [ ] Add Table filters, sorting, and bulk actions with proper authorization policies

14. [ ] Add authorization and policies
    - [ ] Define policies for models (AssetType, Prognosis, ChangeRate)
    - [ ] Ensure Filament resources check policies
    - [ ] Add tests for different roles/teams (team_id present in migrations)

15. [ ] Improve tests: scope, speed, and determinism
    - [ ] Add unit tests for each small service extracted from Prognosis
    - [ ] Add feature tests for queued export flow and status updates
    - [ ] Replace real file comparisons with structured assertions (and Storage fake)
    - [ ] Use model factories and seeders consistently in tests
    - [ ] Remove reliance on current year by fixing the clock (Carbon::setTestNow)

16. [ ] Strengthen database layer
    - [ ] Ensure all models have factories and seeders following conventions
    - [ ] Add missing indexes for frequent queries; review compound indexes
    - [ ] Use casts() methods for attribute casting per Laravel 12 best practices

17. [ ] Introduce static analysis and code quality gates
    - [ ] Configure PHPStan level with a baseline and gradually raise it
    - [ ] Add Larastan if helpful, or configure PHPStan to scan Laravel specifics
    - [ ] Address most critical findings first (types, dead code, unreachable code)

18. [ ] Enforce code style and CI
    - [ ] Run Pint and fix style inconsistencies; add composer script for pint
    - [ ] Add GitHub Actions workflow to run tests, Pint, and PHPStan on PRs
    - [ ] Add code coverage reporting (optional)

19. [ ] Logging and observability
    - [ ] Standardize logging contexts (job id, config id, user id)
    - [ ] Add audit logs for configuration changes (change rates, prognoses)
    - [ ] Consider Laravel Telescope in non-production for debugging

20. [ ] Internationalization and localization
    - [ ] Move hardcoded Norwegian/English strings into lang files
    - [ ] Ensure number/currency formatting respects locale in UI/exports

21. [ ] Improve documentation and onboarding
    - [ ] Update README with clear quick start (dev, tests, frontend build notes)
    - [ ] Document JSON configuration schema and examples
    - [ ] Document queued export flow and Filament admin usage

22. [ ] Validate inputs consistently
    - [ ] Add Form Requests for controllers (if/where applicable)
    - [ ] Add input validation for Livewire/Filament forms
    - [ ] Add configuration schema validation for JSON configs before processing

23. [ ] Reduce god constructors and implicit state
    - [ ] Prefer constructor property promotion and minimal required dependencies
    - [ ] Replace late, mutable assignments with explicit dependency injection

24. [ ] Time/Date handling
    - [ ] Centralize current year/now usage via a Clock interface (injectable)
    - [ ] Replace direct now() with Clock to improve testability

25. [ ] Spreadsheet generation improvements
    - [ ] Extract shared formatting rules to a helper (colors, styles)
    - [ ] Decouple calculation from presentation; accept prepared data structure
    - [ ] Add integration tests that validate structure rather than exact file bytes

26. [ ] Resilience and edge cases
    - [ ] Guard against division by zero and negative year ranges
    - [ ] Validate that transfer sources precede destinations (ordering constraints)
    - [ ] Add safeguards for missing tax rules and provide fallbacks

27. [ ] Security best practices
    - [ ] Ensure no env() calls outside config files
    - [ ] Sanitize and validate all file uploads/paths used for configs
    - [ ] Review public asset overrides (resources/views/vendor/filament, public/js) for XSS risks

28. [ ] Frontend build and assets
    - [ ] Ensure Vite assets are correctly referenced; add troubleshooting in README
    - [ ] Standardize Tailwind classes and remove redundant styles in resources/css/app.css
    - [ ] Add dark mode support consistent with existing patterns

29. [ ] Data modeling for scenarios/prognoses
    - [ ] Implement Eloquent model for Prognosis table with fillable/casts
    - [ ] Seed common prognosis kinds (realistic, positive, negative, zero, variable)
    - [ ] Link Filament resources to manage these records and use them in exports

30. [ ] ChangeRate configuration lifecycle
    - [ ] Implement model + CRUD for change rate configurations
    - [ ] Add validation rules for year ranges and asset type bindings
    - [ ] Add import/export for change rate presets

31. [ ] Domain-driven grouping and summaries
    - [ ] Replace ad-hoc grouping in Prognosis with dedicated aggregator classes
    - [ ] Add tests for group totals (company/private/total) and FIRE metrics

32. [ ] Replace magic numbers/strings with config
    - [ ] Colors used in exports as config constants
    - [ ] Pension age boundaries as config with validation

33. [ ] File storage and retention
    - [ ] Store generated spreadsheets on a configured disk with signed download URLs
    - [ ] Add scheduled cleanup for old exports

34. [ ] Improve migrations robustness
    - [ ] Ensure foreign keys reference correct tables and handle nullable states consistently
    - [ ] Add all attributes when modifying columns per Laravel 12 guidance

35. [ ] Team/multitenancy considerations
    - [ ] Verify scoping by user_id/team_id across queries and UI
    - [ ] Add global scopes or policies to prevent data leakage

36. [ ] Optimize large arrays and memory usage
    - [ ] Stream processing where possible; avoid holding entire lifetime arrays when not necessary
    - [ ] Consider generators/iterators for yearly computations

37. [ ] Developer tooling and scripts
    - [ ] Add make commands (artisan) to scaffold common domain artifacts
    - [ ] Add composer scripts: dev, test:coverage, stan, pint

38. [ ] Cleanup legacy/unused code
    - [ ] Identify and remove dead classes, views, and assets
    - [ ] Ensure tests are updated accordingly

39. [ ] Backfill missing PHPDocs
    - [ ] Add meaningful class/method PHPDocs, especially for public APIs
    - [ ] Use array-shape annotations where arrays persist

40. [ ] Progressive rollout plan
    - [ ] Create an incremental refactor plan with milestones and risks
    - [ ] Track progress using this checklist and issues linked to each item
