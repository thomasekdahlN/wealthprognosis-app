<?php

namespace App\Services;

use App\Exports\AssetSpreadSheet;
use App\Exports\PrognosisAssetSheet2;
use App\Models\SimulationAsset;
use App\Models\SimulationAssetYear;
use App\Models\SimulationConfiguration;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SimulationExportService
{
    public static function export(SimulationConfiguration $simulation, ?string $filePath = null): string
    {
        // Prepare spreadsheet
        $spreadsheet = new Spreadsheet();
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
        $deathYear = $birthYear + (int) ($simulation->death_age ?? 0);

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
            \App\Services\ExcelFormatting::applyCommonAssetSheetFormatting($assetSheet->worksheet, $config['meta'] ?? []);
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
                    ->from((new SimulationAsset())->getTable())
                    ->where('asset_configuration_id', $simulation->id);
            })
            ->min('year');

        return $min ? (int) $min : (int) now()->year - 1;
    }

    protected static function buildAssetArray(SimulationAsset $simAsset): array
    {
        $arr = [];

        foreach ($simAsset->simulationAssetYears as $year) {
            $y = (string) $year->year;

            $arr[$y] = [
                'income' => [
                    'amount' => self::toNum($year->income_amount),
                    'changeratePercent' => 0,
                    'description' => $year->income_description,
                ],
                'expence' => [
                    'amount' => self::toNum($year->expence_amount),
                    'changeratePercent' => 0,
                    'description' => $year->expence_description,
                ],
                'cashflow' => [
                    'amount' => self::toNum($year->cashflow_before_taxamount),
                    'amountAccumulated' => self::toNum($year->cashflow_before_tax_aggregated_amount),
                    'afterTaxAmount' => self::toNum($year->cashflow_after_taxamount),
                    'afterTaxAggregatedAmount' => self::toNum($year->cashflow_after_tax_aggregatedamount),
                    'taxAmount' => self::toNum($year->cashflow_tax_amount),
                    'taxDecimal' => self::percentToDecimal($year->cashflow_tax_percent),
                    'description' => $year->cashflow_description,
                ],
                'mortgage' => [
                    'termAmount' => self::toNum($year->mortgage_term_amount),
                    'interestDecimal' => self::percentToDecimal($year->mortgage_interest_percent),
                    'interestAmount' => self::toNum($year->mortgage_interest_amount),
                    'principalAmount' => self::toNum($year->mortgage_principal_amount),
                    'balanceAmount' => self::toNum($year->mortgage_balance_amount),
                    'taxDeductableAmount' => self::toNum($year->mortgage_tax_deductable_amount),
                    'taxDeductableDecimal' => self::percentToDecimal($year->mortgage_tax_deductable_percent),
                    'description' => $year->mortgage_description,
                ],
                'asset' => [
                    'marketAmount' => self::toNum($year->asset_market_amount),
                    'changeratePercent' => (int) ($year->asset_changerate_percent ?? 0),
                    'marketMortgageDeductedAmount' => self::toNum($year->asset_market_mortgage_deducted_amount),
                    'acquisitionAmount' => self::toNum($year->asset_acquisition_amount),
                    'paidAmount' => self::toNum($year->asset_paid_amount),
                    'taxableAmount' => self::toNum($year->asset_taxable_amount),
                    'taxableDecimal' => self::toNum($year->asset_taxable_percent) > 0 ? self::percentToDecimal($year->asset_taxable_percent) : 0,
                    'taxFortuneAmount' => self::toNum($year->asset_tax_amount),
                    'taxFortuneDecimal' => self::percentToDecimal($year->asset_tax_percent),
                    'taxPropertyAmount' => self::toNum($year->asset_taxable_property_amount),
                    'taxPropertyDecimal' => self::percentToDecimal($year->asset_taxable_property_percent),
                    'mortageRateDecimal' => self::percentToDecimal($year->asset_mortgage_rate_percent ?? 0),
                    'description' => $year->asset_description,
                ],
                'realization' => [
                    'amount' => self::toNum($year->realization_amount),
                    'taxableAmount' => self::toNum($year->realization_taxable_amount),
                    'taxAmount' => self::toNum($year->realization_tax_amount),
                    'taxDecimal' => self::percentToDecimal($year->realization_tax_percent),
                    'taxShieldAmount' => self::toNum($year->realization_tax_shield_amount),
                    'taxShieldDecimal' => self::percentToDecimal($year->realization_tax_shield_percent),
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
                    'savingAmount' => 0,
                    'cashFlow' => 0,
                    'savingRateDecimal' => 0,
                ],
            ];
        }

        ksort($arr);

        return $arr;
    }

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
                $amt = (float) ($data['amount'] ?? 0);
                $stats[$y][$type]['decimal'] = $total > 0 ? $amt / $total : 0;
            }
        }

        ksort($stats);

        return $stats;
    }

    protected static function percentToDecimal($percent): float
    {
        if ($percent === null) {
            return 0.0;
        }
        $p = (float) $percent;
        return $p > 1 ? $p / 100.0 : $p; // Support both 12 and 0.12 inputs
    }

    protected static function toNum($value): float
    {
        return $value !== null ? (float) $value : 0.0;
    }
}

