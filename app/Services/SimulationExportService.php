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
                ->where('asset_configuration_id', $simulation->id);
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
                    'amount' => self::toNum($year->cashflow_before_taxamount),
                    'amountAccumulated' => self::toNum($year->cashflow_before_tax_aggregated_amount),
                    'afterTaxAmount' => self::toNum($year->cashflow_after_taxamount),
                    'afterTaxAggregatedAmount' => self::toNum($year->cashflow_after_tax_aggregatedamount),
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
     * Export SimulationConfiguration to JSON format
     */
    public static function toJson(SimulationConfiguration $simulation): string
    {
        $data = self::buildJsonStructure($simulation);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Build the JSON structure from simulation data
     *
     * @return array<string, mixed>
     */
    protected static function buildJsonStructure(SimulationConfiguration $simulation): array
    {
        $data = [];

        // Add meta section from SimulationConfiguration
        $data['meta'] = [
            'name' => $simulation->name,
            'description' => $simulation->description ?? '',
            'birthYear' => (string) $simulation->birth_year,
            'prognoseAge' => (string) $simulation->prognose_age,
            'pensionOfficialAge' => (string) $simulation->pension_official_age,
            'pensionWishAge' => (string) $simulation->pension_wish_age,
            'deathAge' => (string) $simulation->expected_death_age,
            'exportStartAge' => (string) $simulation->export_start_age,
            'prognosisType' => $simulation->prognosis_type ?? 'realistic',
            'group' => $simulation->group ?? 'both',
            'taxCountry' => $simulation->tax_country ?? 'no',
            'riskTolerance' => $simulation->risk_tolerance ?? 'moderate',
        ];

        // Add timestamps
        if ($simulation->created_at) {
            $data['meta']['createdAt'] = $simulation->created_at->toISOString();
        }
        if ($simulation->updated_at) {
            $data['meta']['updatedAt'] = $simulation->updated_at->toISOString();
        }
        $data['meta']['exportedAt'] = now()->toISOString();

        // Process each simulation asset (ordered by sort_order to maintain JSON sequence)
        $assets = $simulation->simulationAssets()
            ->with('simulationAssetYears')
            ->orderBy('sort_order')
            ->get();

        /** @var \App\Models\SimulationAsset $asset */
        foreach ($assets as $asset) {
            $assetData = [];

            // Add asset meta section
            $assetData['meta'] = [
                'type' => $asset->asset_type,
                'group' => $asset->group,
                'name' => $asset->name,
                'description' => $asset->description ?? '',
                'active' => $asset->is_active,
            ];

            // Process simulation asset years
            $years = $asset->simulationAssetYears()->orderBy('year')->get();

            /** @var \App\Models\SimulationAssetYear $assetYear */
            foreach ($years as $assetYear) {
                $yearKey = (string) $assetYear->year;
                $assetData[$yearKey] = self::buildSimulationYearData($assetYear);
            }

            // Use the asset code as the key
            $data[$asset->code] = $assetData;
        }

        return $data;
    }

    /**
     * Build year data for simulation asset year
     *
     * @return array<string, mixed>
     */
    protected static function buildSimulationYearData(SimulationAssetYear $assetYear): array
    {
        $yearData = [];

        // Income data
        $yearData['income'] = [
            'amount' => self::toNum($assetYear->income_amount),
            'description' => $assetYear->income_description ?? '',
        ];

        // Expense data
        $yearData['expence'] = [
            'amount' => self::toNum($assetYear->expence_amount),
            'description' => $assetYear->expence_description ?? '',
        ];

        // Asset data
        $yearData['asset'] = [
            'marketAmount' => self::toNum($assetYear->asset_market_amount),
            'changeratePercent' => (float) ($assetYear->asset_changerate_percent ?? 0),
            'marketMortgageDeductedAmount' => self::toNum($assetYear->asset_market_mortgage_deducted_amount),
            'acquisitionAmount' => self::toNum($assetYear->asset_acquisition_amount),
            'paidAmount' => self::toNum($assetYear->asset_paid_amount),
            'taxableAmount' => self::toNum($assetYear->asset_taxable_amount),
            'taxablePercent' => (float) ($assetYear->asset_taxable_percent ?? 0),
            'taxAmount' => self::toNum($assetYear->asset_tax_amount),
            'taxPercent' => (float) ($assetYear->asset_tax_percent ?? 0),
            'taxFortuneAmount' => self::toNum($assetYear->asset_tax_fortune_amount),
            'taxPropertyAmount' => self::toNum($assetYear->asset_tax_property_amount),
            'taxablePropertyAmount' => self::toNum($assetYear->asset_taxable_property_amount),
            'taxablePropertyPercent' => (float) ($assetYear->asset_taxable_property_percent ?? 0),
            'mortgageRatePercent' => (float) ($assetYear->asset_mortgage_rate_percent ?? 0),
            'description' => $assetYear->asset_description ?? '',
        ];

        // Mortgage data
        $yearData['mortgage'] = [
            'termAmount' => self::toNum($assetYear->mortgage_term_amount),
            'interestPercent' => (float) ($assetYear->mortgage_interest_percent ?? 0),
            'interestAmount' => self::toNum($assetYear->mortgage_interest_amount),
            'principalAmount' => self::toNum($assetYear->mortgage_principal_amount),
            'balanceAmount' => self::toNum($assetYear->mortgage_balance_amount),
            'taxDeductableAmount' => self::toNum($assetYear->mortgage_tax_deductable_amount),
            'taxDeductablePercent' => (float) ($assetYear->mortgage_tax_deductable_percent ?? 0),
            'description' => $assetYear->mortgage_description ?? '',
        ];

        // Cashflow data
        $yearData['cashflow'] = [
            'beforeTaxAmount' => self::toNum($assetYear->cashflow_before_tax_amount),
            'beforeTaxAggregatedAmount' => self::toNum($assetYear->cashflow_before_tax_aggregated_amount),
            'afterTaxAmount' => self::toNum($assetYear->cashflow_after_tax_amount),
            'afterTaxAggregatedAmount' => self::toNum($assetYear->cashflow_after_tax_aggregated_amount),
            'taxAmount' => self::toNum($assetYear->cashflow_tax_amount),
            'taxPercent' => (float) ($assetYear->cashflow_tax_percent ?? 0),
            'description' => $assetYear->cashflow_description ?? '',
        ];

        // Realization data
        $yearData['realization'] = [
            'amount' => self::toNum($assetYear->realization_amount),
            'taxableAmount' => self::toNum($assetYear->realization_taxable_amount),
            'taxAmount' => self::toNum($assetYear->realization_tax_amount),
            'taxPercent' => (float) ($assetYear->realization_tax_percent ?? 0),
            'taxShieldAmount' => self::toNum($assetYear->realization_tax_shield_amount),
            'taxShieldPercent' => (float) ($assetYear->realization_tax_shield_percent ?? 0),
            'description' => $assetYear->realization_description ?? '',
        ];

        // Yield data
        $yearData['yield'] = [
            'bruttoPercent' => (float) ($assetYear->yield_brutto_percent ?? 0),
            'nettoPercent' => (float) ($assetYear->yield_netto_percent ?? 0),
        ];

        // FIRE metrics
        $yearData['fire'] = [
            'incomeAmount' => self::toNum($assetYear->fire_income_amount),
            'expenceAmount' => self::toNum($assetYear->fire_expence_amount),
            'cashFlowAmount' => self::toNum($assetYear->fire_cashflow_amount),
            'savingAmount' => self::toNum($assetYear->fire_saving_amount),
            'rate' => (float) ($assetYear->fire_rate ?? 0),
            'percent' => (float) ($assetYear->fire_percent ?? 0),
            'savingRate' => (float) ($assetYear->fire_saving_rate ?? 0),
        ];

        // Financial metrics
        $yearData['metrics'] = [
            'ltvPercent' => (float) ($assetYear->metrics_ltv_percent ?? 0),
            'dscr' => (float) ($assetYear->metrics_dscr ?? 0),
            'roiPercent' => (float) ($assetYear->metrics_roi_percent ?? 0),
            'roePercent' => (float) ($assetYear->metrics_roe_percent ?? 0),
            'cocPercent' => (float) ($assetYear->metrics_coc_percent ?? 0),
        ];

        return $yearData;
    }

    /**
     * Static method to get JSON string
     */
    public static function toJsonString(SimulationConfiguration $simulation): string
    {
        return self::toJson($simulation);
    }
}
