<?php

namespace App\Services;

use App\Exports\AssetSpreadSheet;
use App\Exports\PrognosisAssetSheet2;
use App\Models\SimulationAsset;
use App\Models\SimulationAssetYear;
use App\Models\SimulationConfiguration;
use App\Services\Utilities\HelperService;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SimulationExportService
{
    private static HelperService $helperService;

    public static function export(SimulationConfiguration $simulation, ?string $filePath = null): string
    {
        // Initialize helper service
        self::$helperService = new HelperService;

        // Prepare spreadsheet
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Wealth Prognosis')
            ->setLastModifiedBy('Wealth Prognosis')
            ->setTitle('Wealth prognosis (Simulation)')
            ->setSubject('Wealth prognosis Simulation Export')
            ->setDescription('Wealth prognosis export based on simulation data');

        // Remove default first sheet
        $spreadsheet->removeSheetByIndex(0);

        // Build config meta used by PrognosisAssetSheet2
        $birthYear = (int) $simulation->birth_year;
        $thisYear = (int) now()->year;
        $prevYear = $thisYear - 1;
        $prognoseYear = $birthYear + (int) ($simulation->prognose_age ?? 0);
        $pensionOfficialYear = $birthYear + (int) ($simulation->pension_official_age ?? 0);
        $pensionWishYear = $birthYear + (int) ($simulation->pension_wish_age ?? 0);
        $deathYear = $birthYear + (int) ($simulation->expected_death_age ?? 0);

        $config = [
            'meta' => [
                'name' => $simulation->name,
                'birthYear' => $birthYear,
                'exportStartYear' => self::getExportStartYear($simulation),
                'prognoseYear' => $prognoseYear,
                'pensionOfficialYear' => $pensionOfficialYear,
                'pensionWishYear' => $pensionWishYear,
                'deathYear' => $deathYear,
                'thisYear' => $thisYear,
                'prevYear' => $prevYear,
            ],
        ];

        // Load all simulation assets with years
        $assets = $simulation->simulationAssets()
            ->with(['simulationAssetYears' => function ($q) {
                $q->orderBy('year');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Build Statistics structure per year/type
        $statistics = self::buildStatistics($assets);

        // Add one sheet per asset (matching PrognosisExport2 format)
        /** @var \App\Models\SimulationAsset $simAsset */
        foreach ($assets as $simAsset) {
            $meta = [
                'active' => (bool) $simAsset->is_active,
                'name' => $simAsset->name,
                'type' => $simAsset->asset_type,
                'group' => $simAsset->group,
                'description' => $simAsset->description,
            ];

            $assetArray = self::buildAssetArray($simAsset);

            $assetSheet = new PrognosisAssetSheet2($spreadsheet, $config, $assetArray, $meta);
            $spreadsheet->addSheet($assetSheet->worksheet);
            \App\Services\ExcelFormatting::applyCommonAssetSheetFormatting($assetSheet->worksheet, $config['meta']);
        }

        // Add Statistics sheet last (same class as existing export)
        $statsSheet = new AssetSpreadSheet($spreadsheet, $statistics);
        $spreadsheet->addSheet($statsSheet->worksheet);
        \App\Services\ExcelFormatting::applyStatisticsSheetFormatting($statsSheet->worksheet);

        // Save to file
        $exportDir = storage_path('app/exports');
        if (! is_dir($exportDir)) {
            mkdir($exportDir, 0777, true);
        }

        $date = now()->format('Y-m-d');
        $filename = $date.'_'.Str::slug($simulation->name).'_'.$simulation->id.'.xlsx';
        $fullPath = $filePath ?? ($exportDir.DIRECTORY_SEPARATOR.$filename);

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $fullPath;
    }

    protected static function getExportStartYear(SimulationConfiguration $simulation): int
    {
        $min = SimulationAssetYear::whereIn('asset_id', function ($q) use ($simulation) {
            $q->select('id')
                ->from((new SimulationAsset)->getTable())
                ->where('simulation_configuration_id', $simulation->id);
        })
            ->min('year');

        return $min ? (int) $min : (int) now()->year - 1;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function buildAssetArray(SimulationAsset $simAsset): array
    {
        $arr = [];

        /** @var \App\Models\SimulationAssetYear $year */
        foreach ($simAsset->simulationAssetYears as $year) {
            $y = (string) $year->year;

            $arr[$y] = [
                'income' => [
                    'amount' => self::toNum($year->income_amount),
                    'changeratePercent' => 0,
                    'description' => $year->description,
                ],
                'expence' => [
                    'amount' => self::toNum($year->expence_amount),
                    'changeratePercent' => 0,
                    'description' => $year->description,
                ],
                'cashflow' => [
                    'amount' => self::toNum($year->cashflow_before_tax_amount),
                    'amountAccumulated' => self::toNum($year->cashflow_before_tax_aggregated_amount),
                    'afterTaxAmount' => self::toNum($year->cashflow_after_tax_amount),
                    'afterTaxAggregatedAmount' => self::toNum($year->cashflow_after_tax_aggregated_amount),
                    'taxAmount' => self::toNum($year->cashflow_tax_amount),
                    'taxDecimal' => self::$helperService->percentToRate($year->cashflow_tax_percent ?? 0),
                    'description' => $year->cashflow_description,
                ],
                'mortgage' => [
                    'termAmount' => self::toNum($year->mortgage_term_amount),
                    'interestDecimal' => self::$helperService->percentToRate($year->mortgage_interest_percent ?? 0),
                    'interestAmount' => self::toNum($year->mortgage_interest_amount),
                    'principalAmount' => self::toNum($year->mortgage_principal_amount),
                    'balanceAmount' => self::toNum($year->mortgage_balance_amount),
                    'taxDeductableAmount' => self::toNum($year->mortgage_tax_deductable_amount),
                    'taxDeductableDecimal' => self::$helperService->percentToRate($year->mortgage_tax_deductable_percent ?? 0),
                    'description' => $year->description,
                ],
                'asset' => [
                    'marketAmount' => self::toNum($year->asset_market_amount),
                    'changeratePercent' => (int) ($year->asset_changerate_percent ?? 0),
                    'marketMortgageDeductedAmount' => self::toNum($year->asset_market_mortgage_deducted_amount),
                    'acquisitionAmount' => self::toNum($year->asset_acquisition_amount),
                    'paidAmount' => self::toNum($year->asset_paid_amount),
                    'taxableAmount' => self::toNum($year->asset_taxable_amount),
                    'taxableDecimal' => self::toNum($year->asset_taxable_percent) > 0 ? self::$helperService->percentToRate($year->asset_taxable_percent) : 0,
                    'taxFortuneAmount' => self::toNum($year->asset_tax_amount),
                    'taxFortuneDecimal' => self::$helperService->percentToRate($year->asset_tax_percent ?? 0),
                    'taxPropertyAmount' => self::toNum($year->asset_taxable_property_amount),
                    'taxPropertyDecimal' => self::$helperService->percentToRate($year->asset_taxable_property_percent ?? 0),
                    'mortageRateDecimal' => self::$helperService->percentToRate($year->asset_mortgage_rate_percent ?? 0),
                    'description' => $year->description,
                ],
                'realization' => [
                    'amount' => self::toNum($year->realization_amount),
                    'taxableAmount' => self::toNum($year->realization_taxable_amount),
                    'taxAmount' => self::toNum($year->realization_tax_amount),
                    'taxDecimal' => self::$helperService->percentToRate($year->realization_tax_percent ?? 0),
                    'taxShieldAmount' => self::toNum($year->realization_tax_shield_amount),
                    'taxShieldDecimal' => self::$helperService->percentToRate($year->realization_tax_shield_percent ?? 0),
                    'description' => $year->realization_description,
                ],
                'yield' => [
                    'bruttoPercent' => (int) ($year->yield_brutto_percent ?? 0),
                    'nettoPercent' => (int) ($year->yield_netto_percent ?? 0),
                ],
                'potential' => [
                    'incomeAmount' => 0,
                    'mortgageAmount' => 0,
                ],
                'fire' => [
                    'incomeAmount' => 0,
                    'expenceAmount' => 0,
                    'cashFlowAmount' => 0,
                    'savingAmount' => 0,
                    'rate' => 0,
                    'percent' => 0,
                    'savingRate' => 0,
                ],
            ];
        }

        ksort($arr);

        return $arr;
    }

    /**
     * @return array<int, array<string, array<string, float|int>>>
     */
    protected static function buildStatistics($assets): array
    {
        $stats = [];

        foreach ($assets as $asset) {
            foreach ($asset->simulationAssetYears as $year) {
                $y = (int) $year->year;
                $type = $asset->asset_type;
                $amount = (float) ($year->asset_market_amount ?? 0);

                $stats[$y]['total']['amount'] = ($stats[$y]['total']['amount'] ?? 0) + $amount;
                $stats[$y][$type]['amount'] = ($stats[$y][$type]['amount'] ?? 0) + $amount;
            }
        }

        // Compute decimals (share per type of total)
        foreach ($stats as $y => $types) {
            $total = (float) ($types['total']['amount'] ?? 0);
            foreach ($types as $type => $data) {
                if ($type === 'total') {
                    $stats[$y]['total']['decimal'] = 1;

                    continue;
                }
                $amt = (float) $data['amount'];
                $stats[$y][$type]['decimal'] = $total > 0 ? $amt / $total : 0;
            }
        }

        ksort($stats);

        return $stats;
    }

    protected static function toNum($value): float
    {
        return $value !== null ? (float) $value : 0.0;
    }

    /**
     * Export SimulationConfiguration to JSON format for AI analysis
     * Includes simulation info, summary metrics, key years, yearly progression, and asset summaries
     */
    /**
     * Export simulation to complete JSON with ALL fields from database in original import format
     */
    public static function toJson(SimulationConfiguration $simulation): string
    {
        $data = self::buildJsonStructure($simulation, false);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Export simulation to compact JSON for AI analysis (only essential fields)
     */
    public static function toCompactJson(SimulationConfiguration $simulation): string
    {
        $data = self::buildJsonStructure($simulation, true);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Build JSON structure matching original import format
     */
    protected static function buildJsonStructure(SimulationConfiguration $simulation, bool $compactMode = false): array
    {
        $data = [];

        // Add meta section
        $meta = array_filter([
            'name' => $simulation->name,
            'description' => $simulation->description,
            'birthYear' => (string) $simulation->birth_year,
            'pensionWishAge' => (string) $simulation->pension_wish_age,
            'pensionOfficialAge' => (string) $simulation->pension_official_age,
            'deathAge' => (string) $simulation->expected_death_age,
            'riskTolerance' => $simulation->risk_tolerance,
            'prognosisType' => $simulation->prognosis_type,
            'group' => $simulation->group,
            'taxCountry' => $simulation->tax_country,
            'isActive' => $simulation->public,
            'public' => $simulation->public,
        ], fn ($value) => $value !== null && $value !== '');

        $data['meta'] = $meta;

        // Load all simulation assets with their yearly data
        $assets = $simulation->simulationAssets()
            ->with(['simulationAssetYears' => function ($q) {
                $q->orderBy('year');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Process each asset
        foreach ($assets as $asset) {
            /** @var \App\Models\SimulationAsset $asset */
            $assetData = [];

            // Add asset meta section
            $assetMeta = array_filter([
                'type' => $asset->asset_type,
                'group' => $asset->group,
                'name' => $asset->name,
                'description' => $asset->description,
                'active' => $asset->is_active,
                'taxProperty' => $asset->tax_property,
                'taxCountry' => $asset->tax_country,
            ], fn ($value) => $value !== null && $value !== '');

            $assetData['meta'] = $assetMeta;

            // Process asset years (skip rows with all zero amounts)
            foreach ($asset->simulationAssetYears as $yearData) {
                // Skip rows where all amount fields are null or 0
                if (! self::hasNonZeroAmounts($yearData)) {
                    continue;
                }

                $yearKey = (string) $yearData->year;
                $assetData[$yearKey] = self::buildYearData($yearData, $compactMode);
            }

            // Use asset code as key (or name if code is empty)
            $assetKey = ! empty($asset->code) ? $asset->code : $asset->name;
            $data[$assetKey] = $assetData;
        }

        return $data;
    }

    /**
     * Build year data in original import format
     */
    protected static function buildYearData($yearData, bool $compactMode = false): array
    {
        if ($compactMode) {
            return self::buildCompactYearData($yearData);
        }

        $year = [];

        // Description
        if (! empty($yearData->description)) {
            $year['description'] = $yearData->description;
        }

        // Income section
        $income = array_filter([
            'amount' => $yearData->income_amount,
            'changerate' => $yearData->income_changerate,
            'transfer' => $yearData->income_transfer,
            'source' => $yearData->income_source,
            'rule' => $yearData->income_rule,
            'repeat' => $yearData->income_repeat,
        ], fn ($value) => $value !== null && $value !== '');

        if (! empty($income)) {
            $year['income'] = $income;
        }

        // Expense section
        $expence = array_filter([
            'amount' => $yearData->expence_amount,
            'factor' => $yearData->expence_factor,
            'changerate' => $yearData->expence_changerate,
            'transfer' => $yearData->expence_transfer,
            'source' => $yearData->expence_source,
            'rule' => $yearData->expence_rule,
            'repeat' => $yearData->expence_repeat,
        ], fn ($value) => $value !== null && $value !== '');

        if (! empty($expence)) {
            $year['expence'] = $expence;
        }

        // Asset section
        $asset = array_filter([
            'marketAmount' => $yearData->asset_market_amount,
            'acquisitionAmount' => $yearData->asset_acquisition_amount,
            'equityAmount' => $yearData->asset_equity_amount,
            'taxableInitialAmount' => $yearData->asset_taxable_initial_amount,
            'paidAmount' => $yearData->asset_paid_amount,
            'changerate' => $yearData->asset_changerate,
            'rule' => $yearData->asset_rule,
            'transfer' => $yearData->asset_transfer,
            'source' => $yearData->asset_source,
            'repeat' => $yearData->asset_repeat,
        ], fn ($value) => $value !== null && $value !== '');

        if (! empty($asset)) {
            $year['asset'] = $asset;
        }

        // Mortgage section
        $mortgage = array_filter([
            'amount' => $yearData->mortgage_amount,
            'years' => $yearData->mortgage_years,
            'interest' => $yearData->mortgage_interest,
            'gebyr' => $yearData->mortgage_gebyr,
            'tax' => $yearData->mortgage_tax,
            'paymentExtra' => $yearData->mortgage_extra_downpayment_amount,
        ], fn ($value) => $value !== null && $value !== '');

        if (! empty($mortgage)) {
            $year['mortgage'] = $mortgage;
        }

        return $year;
    }

    /**
     * Build compact year data for AI analysis (only essential calculated fields)
     */
    protected static function buildCompactYearData($yearData): array
    {
        return array_filter([
            'year' => $yearData->year,
            'incomeAmount' => $yearData->income_amount,
            'expenceAmount' => $yearData->expence_amount,
            'cashflowAfterTaxAmount' => $yearData->cashflow_after_tax_amount,
            'cashflowTaxAmount' => $yearData->cashflow_tax_amount,
            'assetMarketAmount' => $yearData->asset_market_amount,
            'assetMarketMortgageDeductedAmount' => $yearData->asset_market_mortgage_deducted_amount,
            'mortgageBalanceAmount' => $yearData->mortgage_balance_amount,
            'mortgageInterestAmount' => $yearData->mortgage_interest_amount,
            'assetTaxAmount' => $yearData->asset_tax_amount,
            'realizationTaxAmount' => $yearData->realization_tax_amount,
            'firePercent' => $yearData->fire_percent,
            'metricsLtvPercent' => $yearData->metrics_ltv_percent,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Export simulation to compact CSV format for AI analysis
     * More compact than JSON - uses flat structure with one row per asset-year
     * Only includes essential calculated fields
     * Only includes rows where at least one amount field is non-zero
     */
    public static function toCsvCompact(SimulationConfiguration $simulation): string
    {
        $output = [];

        // CSV Header
        $header = [
            'simulation_name',
            'simulation_description',
            'birth_year',
            'pension_wish_age',
            'pension_official_age',
            'death_age',
            'risk_tolerance',
            'prognosis_type',
            'group',
            'tax_country',
            'asset_name',
            'asset_type',
            'asset_group',
            'asset_description',
            'year',
            'income_amount',
            'expence_amount',
            'cashflow_after_tax_amount',
            'cashflow_tax_amount',
            'asset_market_amount',
            'asset_market_mortgage_deducted_amount',
            'mortgage_balance_amount',
            'mortgage_interest_amount',
            'asset_tax_amount',
            'realization_tax_amount',
            'fire_percent',
            'metrics_ltv_percent',
        ];

        $output[] = implode(',', $header);

        // Load all simulation assets with their yearly data
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\SimulationAsset> $assets */
        $assets = $simulation->simulationAssets()
            ->with(['simulationAssetYears' => function ($q) {
                $q->orderBy('year');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Process each asset and year
        foreach ($assets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                // Skip rows where all amount fields are null or 0
                if (! self::hasNonZeroAmounts($yearData)) {
                    continue;
                }

                $row = [
                    self::csvEscape($simulation->name),
                    self::csvEscape($simulation->description),
                    $simulation->birth_year,
                    $simulation->pension_wish_age,
                    $simulation->pension_official_age,
                    $simulation->expected_death_age,
                    self::csvEscape($simulation->risk_tolerance),
                    self::csvEscape($simulation->prognosis_type),
                    self::csvEscape($simulation->group),
                    self::csvEscape($simulation->tax_country),
                    self::csvEscape($asset->name),
                    self::csvEscape($asset->asset_type),
                    self::csvEscape($asset->group),
                    self::csvEscape((string) $asset->description),
                    $yearData->year,
                    $yearData->income_amount ?? 0,
                    $yearData->expence_amount ?? 0,
                    $yearData->cashflow_after_tax_amount ?? 0,
                    $yearData->cashflow_tax_amount ?? 0,
                    $yearData->asset_market_amount ?? 0,
                    $yearData->asset_market_mortgage_deducted_amount ?? 0,
                    $yearData->mortgage_balance_amount ?? 0,
                    $yearData->mortgage_interest_amount ?? 0,
                    $yearData->asset_tax_amount ?? 0,
                    $yearData->realization_tax_amount ?? 0,
                    $yearData->fire_percent ?? 0,
                    $yearData->metrics_ltv_percent ?? 0,
                ];

                $output[] = implode(',', $row);
            }
        }

        return implode("\n", $output);
    }

    /**
     * Export simulation to full CSV format with ALL columns from simulation_asset_years table
     * Includes all fields for complete data export
     * Only includes rows where at least one amount field is non-zero
     */
    public static function toCsvFull(SimulationConfiguration $simulation): string
    {
        $output = [];

        // CSV Header - ALL columns from simulation_asset_years table
        $header = [
            // Simulation metadata
            'simulation_name',
            'simulation_description',
            'birth_year',
            'pension_wish_age',
            'pension_official_age',
            'death_age',
            'risk_tolerance',
            'prognosis_type',
            'group',
            'tax_country',
            // Asset metadata
            'asset_name',
            'asset_type',
            'asset_group',
            'asset_description',
            // Year data
            'year',
            'description',
            // Income fields
            'income_amount',
            'income_factor',
            'income_rule',
            'income_transfer',
            'income_transfer_amount',
            'income_source',
            'income_changerate',
            'income_changerate_percent',
            'income_repeat',
            'income_description',
            // Expense fields
            'expence_amount',
            'expence_factor',
            'expence_rule',
            'expence_transfer',
            'expence_transfer_amount',
            'expence_source',
            'expence_changerate',
            'expence_changerate_percent',
            'expence_repeat',
            'expence_description',
            // Cashflow fields
            'cashflow_description',
            'cashflow_after_tax_amount',
            'cashflow_before_tax_amount',
            'cashflow_before_tax_aggregated_amount',
            'cashflow_after_tax_aggregated_amount',
            'cashflow_tax_amount',
            'cashflow_tax_percent',
            'cashflow_rule',
            'cashflow_transfer',
            'cashflow_transfer_amount',
            'cashflow_source',
            'cashflow_changerate',
            'cashflow_repeat',
            // Asset fields
            'asset_market_amount',
            'asset_market_mortgage_deducted_amount',
            'asset_acquisition_amount',
            'asset_acquisition_initial_amount',
            'asset_equity_amount',
            'asset_equity_initial_amount',
            'asset_paid_amount',
            'asset_paid_initial_amount',
            'asset_transfered_amount',
            'asset_taxable_percent',
            'asset_taxable_amount',
            'asset_taxable_initial_amount',
            'asset_taxable_amount_override',
            'asset_tax_percent',
            'asset_tax_amount',
            'asset_taxable_property_percent',
            'asset_taxable_property_amount',
            'asset_tax_property_percent',
            'asset_tax_property_amount',
            'asset_taxable_fortune_amount',
            'asset_taxable_fortune_percent',
            'asset_tax_fortune_amount',
            'asset_tax_fortune_percent',
            'asset_gjeldsfradrag_amount',
            'asset_changerate',
            'asset_changerate_percent',
            'asset_rule',
            'asset_transfer',
            'asset_source',
            'asset_repeat',
            // Mortgage fields
            'mortgage_amount',
            'mortgage_term_amount',
            'mortgage_interest_amount',
            'mortgage_principal_amount',
            'mortgage_balance_amount',
            'mortgage_extra_downpayment_amount',
            'mortgage_transfered_amount',
            'mortgage_interest_percent',
            'mortgage_years',
            'mortgage_interest_only_years',
            'mortgage_gebyr_amount',
            'mortgage_tax_deductable_amount',
            'mortgage_tax_deductable_percent',
            'mortgage_description',
            // Realization fields
            'realization_description',
            'realization_amount',
            'realization_taxable_amount',
            'realization_tax_amount',
            'realization_tax_percent',
            'realization_tax_shield_amount',
            'realization_tax_shield_percent',
            // Yield fields
            'yield_gross_percent',
            'yield_net_percent',
            'yield_cap_percent',
            // Potential fields
            'potential_income_amount',
            'potential_mortgage_amount',
            // Metrics fields
            'metrics_roi_percent',
            'metrics_total_return_amount',
            'metrics_total_return_percent',
            'metrics_coc_percent',
            'metrics_noi',
            'metrics_grm',
            'metrics_dscr',
            'metrics_ltv_percent',
            'metrics_de_ratio',
            'metrics_roe_percent',
            'metrics_roa_percent',
            'metrics_pb_ratio',
            'metrics_ev_ebitda',
            'metrics_current_ratio',
            // F.I.R.E. fields
            'fire_percent',
            'fire_income_amount',
            'fire_expence_amount',
            'fire_cashflow_amount',
            'fire_saving_amount',
            'fire_saving_rate_percent',
        ];

        $output[] = implode(',', $header);

        // Load all simulation assets with their yearly data
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\SimulationAsset> $assets */
        $assets = $simulation->simulationAssets()
            ->with(['simulationAssetYears' => function ($q) {
                $q->orderBy('year');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Process each asset and year
        foreach ($assets as $asset) {
            foreach ($asset->simulationAssetYears as $yearData) {
                // Skip rows where all amount fields are null or 0
                if (! self::hasNonZeroAmounts($yearData)) {
                    continue;
                }

                $row = [
                    // Simulation metadata
                    self::csvEscape($simulation->name),
                    self::csvEscape($simulation->description),
                    $simulation->birth_year,
                    $simulation->pension_wish_age,
                    $simulation->pension_official_age,
                    $simulation->expected_death_age,
                    self::csvEscape($simulation->risk_tolerance),
                    self::csvEscape($simulation->prognosis_type),
                    self::csvEscape($simulation->group),
                    self::csvEscape($simulation->tax_country),
                    // Asset metadata
                    self::csvEscape($asset->name),
                    self::csvEscape($asset->asset_type),
                    self::csvEscape($asset->group),
                    self::csvEscape($asset->description),
                    // Year data
                    $yearData->year,
                    self::csvEscape($yearData->description),
                    // Income fields (continuing in next chunk due to 150 line limit)
                ];

                // Add all remaining fields
                $row = array_merge($row, [
                    // Income fields
                    $yearData->income_amount ?? 0,
                    self::csvEscape($yearData->income_factor),
                    self::csvEscape($yearData->income_rule),
                    self::csvEscape($yearData->income_transfer),
                    $yearData->income_transfer_amount ?? 0,
                    self::csvEscape($yearData->income_source),
                    self::csvEscape($yearData->income_changerate),
                    $yearData->income_changerate_percent ?? 0,
                    $yearData->income_repeat ? 1 : 0,
                    self::csvEscape($yearData->income_description),
                    // Expense fields
                    $yearData->expence_amount ?? 0,
                    self::csvEscape($yearData->expence_factor),
                    self::csvEscape($yearData->expence_rule),
                    self::csvEscape($yearData->expence_transfer),
                    $yearData->expence_transfer_amount ?? 0,
                    self::csvEscape($yearData->expence_source),
                    self::csvEscape($yearData->expence_changerate),
                    $yearData->expence_changerate_percent ?? 0,
                    $yearData->expence_repeat ? 1 : 0,
                    self::csvEscape($yearData->expence_description),
                    // Cashflow fields
                    self::csvEscape($yearData->cashflow_description),
                    $yearData->cashflow_after_tax_amount ?? 0,
                    $yearData->cashflow_before_tax_amount ?? 0,
                    $yearData->cashflow_before_tax_aggregated_amount ?? 0,
                    $yearData->cashflow_after_tax_aggregated_amount ?? 0,
                    $yearData->cashflow_tax_amount ?? 0,
                    $yearData->cashflow_tax_percent ?? 0,
                    self::csvEscape($yearData->cashflow_rule),
                    self::csvEscape($yearData->cashflow_transfer),
                    $yearData->cashflow_transfer_amount ?? 0,
                    self::csvEscape($yearData->cashflow_source),
                    self::csvEscape($yearData->cashflow_changerate),
                    $yearData->cashflow_repeat ? 1 : 0,
                    // Asset fields
                    $yearData->asset_market_amount ?? 0,
                    $yearData->asset_market_mortgage_deducted_amount ?? 0,
                    $yearData->asset_acquisition_amount ?? 0,
                    $yearData->asset_acquisition_initial_amount ?? 0,
                    $yearData->asset_equity_amount ?? 0,
                    $yearData->asset_equity_initial_amount ?? 0,
                    $yearData->asset_paid_amount ?? 0,
                    $yearData->asset_paid_initial_amount ?? 0,
                    $yearData->asset_transfered_amount ?? 0,
                    $yearData->asset_taxable_percent ?? 0,
                    $yearData->asset_taxable_amount ?? 0,
                    $yearData->asset_taxable_initial_amount ?? 0,
                    $yearData->asset_taxable_amount_override ? 1 : 0,
                    $yearData->asset_tax_percent ?? 0,
                    $yearData->asset_tax_amount ?? 0,
                    $yearData->asset_taxable_property_percent ?? 0,
                    $yearData->asset_taxable_property_amount ?? 0,
                    $yearData->asset_tax_property_percent ?? 0,
                    $yearData->asset_tax_property_amount ?? 0,
                    $yearData->asset_taxable_fortune_amount ?? 0,
                    $yearData->asset_taxable_fortune_percent ?? 0,
                    $yearData->asset_tax_fortune_amount ?? 0,
                    $yearData->asset_tax_fortune_percent ?? 0,
                    $yearData->asset_gjeldsfradrag_amount ?? 0,
                    self::csvEscape($yearData->asset_changerate),
                    $yearData->asset_changerate_percent ?? 0,
                    self::csvEscape($yearData->asset_rule),
                    self::csvEscape($yearData->asset_transfer),
                    self::csvEscape($yearData->asset_source),
                    $yearData->asset_repeat ? 1 : 0,
                    // Mortgage fields
                    $yearData->mortgage_amount ?? 0,
                    $yearData->mortgage_term_amount ?? 0,
                    $yearData->mortgage_interest_amount ?? 0,
                    $yearData->mortgage_principal_amount ?? 0,
                    $yearData->mortgage_balance_amount ?? 0,
                    $yearData->mortgage_extra_downpayment_amount ?? 0,
                    $yearData->mortgage_transfered_amount ?? 0,
                    $yearData->mortgage_interest_percent ?? 0,
                    $yearData->mortgage_years ?? 0,
                    $yearData->mortgage_interest_only_years ?? 0,
                    $yearData->mortgage_gebyr_amount ?? 0,
                    $yearData->mortgage_tax_deductable_amount ?? 0,
                    $yearData->mortgage_tax_deductable_percent ?? 0,
                    self::csvEscape($yearData->mortgage_description),
                    // Realization fields
                    self::csvEscape($yearData->realization_description),
                    $yearData->realization_amount ?? 0,
                    $yearData->realization_taxable_amount ?? 0,
                    $yearData->realization_tax_amount ?? 0,
                    $yearData->realization_tax_percent ?? 0,
                    $yearData->realization_tax_shield_amount ?? 0,
                    $yearData->realization_tax_shield_percent ?? 0,
                    // Yield fields
                    $yearData->yield_gross_percent ?? 0,
                    $yearData->yield_net_percent ?? 0,
                    $yearData->yield_cap_percent ?? 0,
                    // Potential fields
                    $yearData->potential_income_amount ?? 0,
                    $yearData->potential_mortgage_amount ?? 0,
                    // Metrics fields
                    $yearData->metrics_roi_percent ?? 0,
                    $yearData->metrics_total_return_amount ?? 0,
                    $yearData->metrics_total_return_percent ?? 0,
                    $yearData->metrics_coc_percent ?? 0,
                    $yearData->metrics_noi ?? 0,
                    $yearData->metrics_grm ?? 0,
                    $yearData->metrics_dscr ?? 0,
                    $yearData->metrics_ltv_percent ?? 0,
                    $yearData->metrics_de_ratio ?? 0,
                    $yearData->metrics_roe_percent ?? 0,
                    $yearData->metrics_roa_percent ?? 0,
                    $yearData->metrics_pb_ratio ?? 0,
                    $yearData->metrics_ev_ebitda ?? 0,
                    $yearData->metrics_current_ratio ?? 0,
                    // F.I.R.E. fields
                    $yearData->fire_percent ?? 0,
                    $yearData->fire_income_amount ?? 0,
                    $yearData->fire_expence_amount ?? 0,
                    $yearData->fire_cashflow_amount ?? 0,
                    $yearData->fire_saving_amount ?? 0,
                    $yearData->fire_saving_rate_percent ?? 0,
                ]);

                $output[] = implode(',', $row);
            }
        }

        return implode("\n", $output);
    }

    /**
     * Escape CSV field (handle commas, quotes, newlines)
     */
    protected static function csvEscape(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // If contains comma, quote, or newline, wrap in quotes and escape quotes
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }

    /**
     * Check if a simulation asset year has any non-zero amount fields
     */
    protected static function hasNonZeroAmounts(SimulationAssetYear $yearData): bool
    {
        $amountFields = [
            'income_amount',
            'income_transfer_amount',
            'expence_amount',
            'expence_transfer_amount',
            'cashflow_after_tax_amount',
            'cashflow_before_tax_amount',
            'cashflow_before_tax_aggregated_amount',
            'cashflow_after_tax_aggregated_amount',
            'cashflow_tax_amount',
            'cashflow_transfer_amount',
            'asset_market_amount',
            'asset_market_mortgage_deducted_amount',
            'asset_acquisition_amount',
            'asset_acquisition_initial_amount',
            'asset_equity_amount',
            'asset_equity_initial_amount',
            'asset_paid_amount',
            'asset_paid_initial_amount',
            'asset_transfered_amount',
            'asset_taxable_amount',
            'asset_taxable_initial_amount',
            'asset_tax_amount',
            'asset_taxable_property_amount',
            'asset_tax_property_amount',
            'asset_taxable_fortune_amount',
            'asset_tax_fortune_amount',
            'asset_gjeldsfradrag_amount',
            'mortgage_amount',
            'mortgage_term_amount',
            'mortgage_interest_amount',
            'mortgage_principal_amount',
            'mortgage_balance_amount',
            'mortgage_extra_downpayment_amount',
            'mortgage_transfered_amount',
            'mortgage_gebyr_amount',
            'mortgage_tax_deductable_amount',
            'realization_amount',
            'realization_taxable_amount',
            'realization_tax_amount',
            'realization_tax_shield_amount',
            'potential_income_amount',
            'potential_mortgage_amount',
            'metrics_total_return_amount',
            'fire_income_amount',
            'fire_expence_amount',
            'fire_cashflow_amount',
            'fire_saving_amount',
        ];

        foreach ($amountFields as $field) {
            $value = $yearData->$field;
            if ($value !== null && $value != 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Export simulation to Excel format and return file path
     * Uses the existing export() method
     */
    public static function toExcel(SimulationConfiguration $simulation): string
    {
        return self::export($simulation);
    }
}
