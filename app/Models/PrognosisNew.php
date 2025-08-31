<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;

class PrognosisNew
{
    protected AssetConfiguration $assetConfiguration;
    protected string $prognosisType;
    protected string $assetScope;
    protected int $startYear;
    protected int $endYear;
    protected array $results = [];
    protected array $yearlyData = [];
    protected array $assetBreakdown = [];

    // Economic scenarios with different growth rates
    protected array $economicScenarios = [
        'realistic' => [
            'inflation' => 0.025,
            'stock_growth' => 0.07,
            'bond_growth' => 0.04,
            'real_estate_growth' => 0.05,
            'cash_growth' => 0.02,
        ],
        'positive' => [
            'inflation' => 0.02,
            'stock_growth' => 0.10,
            'bond_growth' => 0.05,
            'real_estate_growth' => 0.07,
            'cash_growth' => 0.03,
        ],
        'negative' => [
            'inflation' => 0.04,
            'stock_growth' => 0.03,
            'bond_growth' => 0.02,
            'real_estate_growth' => 0.02,
            'cash_growth' => 0.01,
        ],
        'tenpercent' => [
            'inflation' => 0.02,
            'stock_growth' => 0.12,
            'bond_growth' => 0.06,
            'real_estate_growth' => 0.08,
            'cash_growth' => 0.04,
        ],
        'zero' => [
            'inflation' => 0.02,
            'stock_growth' => 0.02,
            'bond_growth' => 0.02,
            'real_estate_growth' => 0.02,
            'cash_growth' => 0.02,
        ],
        'variable' => [
            'inflation' => 0.025,
            'stock_growth' => 0.08,
            'bond_growth' => 0.04,
            'real_estate_growth' => 0.06,
            'cash_growth' => 0.025,
        ],
    ];

    public function __construct(AssetConfiguration $assetConfiguration, string $prognosisType = 'realistic', string $assetScope = 'both')
    {
        $this->assetConfiguration = $assetConfiguration;
        $this->prognosisType = $prognosisType;
        $this->assetScope = $assetScope;
        $this->startYear = (int) date('Y');
        $this->endYear = $assetConfiguration->birth_year + $assetConfiguration->death_age;
    }

    /**
     * Run the complete simulation
     */
    public function runSimulation(): array
    {
        Log::info('Starting PrognosisNew simulation', [
            'asset_configuration_id' => $this->assetConfiguration->id,
            'prognosis_type' => $this->prognosisType,
            'asset_scope' => $this->assetScope,
            'start_year' => $this->startYear,
            'end_year' => $this->endYear,
        ]);

        // Get filtered assets based on scope
        $assets = $this->getFilteredAssets();
        
        // Initialize yearly data structure
        $this->initializeYearlyData();
        
        // Process each asset for each year
        foreach ($assets as $asset) {
            $this->processAssetOverTime($asset);
        }
        
        // Calculate summary statistics
        $this->calculateSummaryStats();
        
        // Prepare final results
        $this->results = [
            'configuration' => [
                'asset_configuration_id' => $this->assetConfiguration->id,
                'prognosis_type' => $this->prognosisType,
                'asset_scope' => $this->assetScope,
                'start_year' => $this->startYear,
                'end_year' => $this->endYear,
                'duration_years' => $this->endYear - $this->startYear + 1,
            ],
            'summary' => $this->calculateSummaryStats(),
            'yearly_data' => $this->yearlyData,
            'asset_breakdown' => $this->assetBreakdown,
            'economic_scenario' => $this->economicScenarios[$this->prognosisType],
        ];

        Log::info('PrognosisNew simulation completed', [
            'asset_configuration_id' => $this->assetConfiguration->id,
            'years_processed' => count($this->yearlyData),
            'assets_processed' => count($assets),
        ]);

        return $this->results;
    }

    /**
     * Get assets filtered by scope
     */
    protected function getFilteredAssets()
    {
        $query = $this->assetConfiguration->assets()->where('is_active', true);
        
        if ($this->assetScope === 'private') {
            $query->where('group', 'private');
        } elseif ($this->assetScope === 'business') {
            $query->where('group', 'business');
        }
        // 'both' includes all assets
        
        return $query->with(['assetYears', 'assetType', 'taxType'])->get();
    }

    /**
     * Initialize yearly data structure
     */
    protected function initializeYearlyData(): void
    {
        for ($year = $this->startYear; $year <= $this->endYear; $year++) {
            $this->yearlyData[$year] = [
                'year' => $year,
                'age' => $year - $this->assetConfiguration->birth_year,
                'total_assets' => 0,
                'total_income' => 0,
                'total_expenses' => 0,
                'total_taxes' => 0,
                'net_worth' => 0,
                'assets' => [],
            ];
        }
    }

    /**
     * Process a single asset over all years
     */
    protected function processAssetOverTime(Asset $asset): void
    {
        // Get the most recent asset year data as baseline
        $baselineAssetYear = $asset->assetYears()
            ->where('year', '<=', $this->startYear)
            ->orderBy('year', 'desc')
            ->first();

        if (!$baselineAssetYear) {
            // If no baseline data, skip this asset
            Log::warning('No baseline data for asset', ['asset_id' => $asset->id]);
            return;
        }

        $currentMarketAmount = $baselineAssetYear->market_amount;
        $currentAcquisitionAmount = $baselineAssetYear->acquisition_amount;
        $currentEquityAmount = $baselineAssetYear->equity_amount;

        // Get growth rate for this asset type
        $growthRate = $this->getGrowthRateForAsset($asset);

        for ($year = $this->startYear; $year <= $this->endYear; $year++) {
            // Calculate asset growth
            if ($year > $this->startYear) {
                $currentMarketAmount *= (1 + $growthRate);
                $currentEquityAmount *= (1 + $growthRate);
            }

            // Calculate income and expenses for this year
            $yearlyIncome = $this->calculateYearlyIncome($baselineAssetYear, $year);
            $yearlyExpenses = $this->calculateYearlyExpenses($baselineAssetYear, $year);
            $yearlyTaxes = $this->calculateYearlyTaxes($asset, $currentMarketAmount, $currentAcquisitionAmount, $yearlyIncome);

            // Store asset data for this year
            $assetData = [
                'asset_id' => $asset->id,
                'asset_name' => $asset->name,
                'asset_type' => $asset->asset_type,
                'group' => $asset->group,
                'market_amount' => $currentMarketAmount,
                'acquisition_amount' => $currentAcquisitionAmount,
                'equity_amount' => $currentEquityAmount,
                'income' => $yearlyIncome,
                'expenses' => $yearlyExpenses,
                'taxes' => $yearlyTaxes,
                'net_value' => $currentMarketAmount - $yearlyExpenses - $yearlyTaxes,
            ];

            $this->yearlyData[$year]['assets'][] = $assetData;
            $this->yearlyData[$year]['total_assets'] += $currentMarketAmount;
            $this->yearlyData[$year]['total_income'] += $yearlyIncome;
            $this->yearlyData[$year]['total_expenses'] += $yearlyExpenses;
            $this->yearlyData[$year]['total_taxes'] += $yearlyTaxes;
        }

        // Store asset breakdown
        $this->assetBreakdown[$asset->id] = [
            'asset_name' => $asset->name,
            'asset_type' => $asset->asset_type,
            'group' => $asset->group,
            'start_value' => $baselineAssetYear->market_amount,
            'end_value' => $currentMarketAmount,
            'total_growth' => $currentMarketAmount - $baselineAssetYear->market_amount,
            'growth_rate' => $growthRate,
        ];
    }

    /**
     * Get growth rate for specific asset type
     */
    protected function getGrowthRateForAsset(Asset $asset): float
    {
        $scenario = $this->economicScenarios[$this->prognosisType];
        
        return match($asset->asset_type) {
            'equity', 'stock', 'mutual_fund' => $scenario['stock_growth'],
            'bond', 'fixed_income' => $scenario['bond_growth'],
            'real_estate', 'property' => $scenario['real_estate_growth'],
            'cash', 'savings' => $scenario['cash_growth'],
            default => $scenario['stock_growth'], // Default to stock growth
        };
    }

    /**
     * Calculate yearly income from asset
     */
    protected function calculateYearlyIncome(AssetYear $assetYear, int $year): float
    {
        $income = $assetYear->income_amount;
        
        if ($assetYear->income_factor === 'monthly') {
            $income *= 12;
        }
        
        // Apply inflation adjustment
        $yearsFromStart = $year - $this->startYear;
        $inflationRate = $this->economicScenarios[$this->prognosisType]['inflation'];
        $income *= pow(1 + $inflationRate, $yearsFromStart);
        
        return $income;
    }

    /**
     * Calculate yearly expenses from asset
     */
    protected function calculateYearlyExpenses(AssetYear $assetYear, int $year): float
    {
        $expenses = $assetYear->expence_amount;
        
        if ($assetYear->expence_factor === 'monthly') {
            $expenses *= 12;
        }
        
        // Apply inflation adjustment
        $yearsFromStart = $year - $this->startYear;
        $inflationRate = $this->economicScenarios[$this->prognosisType]['inflation'];
        $expenses *= pow(1 + $inflationRate, $yearsFromStart);
        
        return $expenses;
    }

    /**
     * Calculate yearly taxes (simplified calculation)
     */
    protected function calculateYearlyTaxes(Asset $asset, float $marketAmount, float $acquisitionAmount, float $income): float
    {
        $taxes = 0;
        
        // Income tax on income
        if ($income > 0) {
            $taxes += $income * 0.25; // Simplified 25% income tax
        }
        
        // Capital gains tax (simplified)
        if ($asset->tax_type === 'capital_gains') {
            $capitalGains = max(0, $marketAmount - $acquisitionAmount);
            if ($capitalGains > 0) {
                $taxes += $capitalGains * 0.20; // Simplified 20% capital gains tax
            }
        }
        
        // Wealth tax (simplified)
        if ($marketAmount > 2000000) { // Above 2M NOK
            $taxes += ($marketAmount - 2000000) * 0.01; // 1% wealth tax
        }
        
        return $taxes;
    }

    /**
     * Calculate summary statistics
     */
    protected function calculateSummaryStats(): array
    {
        $firstYear = $this->yearlyData[$this->startYear] ?? [];
        $lastYear = $this->yearlyData[$this->endYear] ?? [];
        
        $totalAssetsStart = $firstYear['total_assets'] ?? 0;
        $totalAssetsEnd = $lastYear['total_assets'] ?? 0;
        
        $totalIncome = array_sum(array_column($this->yearlyData, 'total_income'));
        $totalExpenses = array_sum(array_column($this->yearlyData, 'total_expenses'));
        $totalTaxes = array_sum(array_column($this->yearlyData, 'total_taxes'));
        
        // Calculate FIRE metrics
        $fireAchieved = false;
        $fireYear = null;
        $fireAmount = $totalExpenses * 25; // 4% rule
        
        foreach ($this->yearlyData as $year => $data) {
            if ($data['total_assets'] >= $fireAmount && !$fireAchieved) {
                $fireAchieved = true;
                $fireYear = $year;
                break;
            }
        }
        
        return [
            'total_assets_start' => $totalAssetsStart,
            'total_assets_end' => $totalAssetsEnd,
            'net_worth_change' => $totalAssetsEnd - $totalAssetsStart,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'total_taxes' => $totalTaxes,
            'net_cash_flow' => $totalIncome - $totalExpenses - $totalTaxes,
            'fire_achieved' => $fireAchieved,
            'fire_year' => $fireYear,
            'fire_amount_needed' => $fireAmount,
            'years_to_fire' => $fireYear ? $fireYear - $this->startYear : null,
        ];
    }

    /**
     * Get results
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
