<?php

namespace App\Services;

use App\Models\AssetConfiguration;
use App\Models\SimulationAsset;
use App\Models\SimulationAssetYear;
use App\Models\SimulationConfiguration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PrognosisSimulationService
{
    /**
     * Run a complete simulation based on parameters
     *
     * @param  array<string, mixed>  $simulationData
     * @return array<string, mixed>
     */
    public function runSimulation(array $simulationData): array
    {
        $assetConfigurationId = $simulationData['asset_configuration_id'];
        $prognosisType = $simulationData['prognosis_type'];
        $assetScope = $simulationData['group'] ?? $simulationData['asset_scope']; // Support both field names
        $taxCountry = $simulationData['tax_country'] ?? 'no';

        Log::info('Starting PrognosisSimulationService', [
            'asset_configuration_id' => $assetConfigurationId,
            'prognosis_type' => $prognosisType,
            'asset_scope' => $assetScope,
            'tax_country' => $taxCountry,
        ]);

        // Get the asset configuration
        $assetConfiguration = AssetConfiguration::with(['assets.years', 'assets.assetType'])
            ->findOrFail($assetConfigurationId);

        // Create simulation configuration record
        $simulationConfig = $this->createSimulationConfiguration($assetConfiguration, $prognosisType, $assetScope, $taxCountry);

        // Copy assets to simulation tables
        $this->copyAssetsToSimulation($assetConfiguration, $simulationConfig, $assetScope);

        // TODO: Implement prognosis calculation engine
        // For now, return basic structure
        $results = [
            'configuration' => [
                'asset_configuration_id' => $assetConfiguration->id,
                'prognosis_type' => $prognosisType,
                'asset_scope' => $assetScope,
            ],
            'summary' => [
                'total_assets_start' => 0,
                'total_assets_end' => 0,
                'total_income' => 0,
                'total_expenses' => 0,
            ],
            'yearly_data' => [],
            'asset_breakdown' => [],
        ];

        // Store detailed results in simulation tables
        $this->storeSimulationResults($simulationConfig, $results);

        Log::info('PrognosisSimulationService completed', [
            'simulation_configuration_id' => $simulationConfig->id,
            'years_processed' => count($results['yearly_data']),
        ]);

        return [
            'simulation_configuration_id' => $simulationConfig->id,
            'results' => $results,
            'summary' => $results['summary'],
        ];
    }

    /**
     * Create simulation configuration record
     */
    protected function createSimulationConfiguration(AssetConfiguration $assetConfig, string $prognosisType, string $assetScope, string $taxCountry = 'no'): SimulationConfiguration
    {
        return SimulationConfiguration::create([
            'asset_configuration_id' => $assetConfig->id,
            'name' => "Simulation - {$assetConfig->name} ({$prognosisType})",
            'description' => "Financial simulation using {$prognosisType} scenario for {$assetScope} assets with {$taxCountry} tax system",
            'birth_year' => $assetConfig->birth_year,
            'prognose_age' => $assetConfig->prognose_age,
            'pension_official_age' => $assetConfig->pension_official_age,
            'pension_wish_age' => $assetConfig->pension_wish_age,
            'expected_death_age' => $assetConfig->expected_death_age,
            'export_start_age' => $assetConfig->export_start_age,
            'public' => false,
            'risk_tolerance' => $this->mapPrognosisTypeToRiskTolerance($prognosisType),
            'tax_country' => $taxCountry,
            'prognosis_type' => $prognosisType,
            'group' => $assetScope,
            'tags' => [$prognosisType, $assetScope, $taxCountry, 'simulation'],
            'user_id' => Auth::id() ?? $assetConfig->user_id,
            'team_id' => Auth::user()->currentTeam->id ?? $assetConfig->team_id,
            'created_by' => Auth::id() ?? $assetConfig->user_id,
            'updated_by' => Auth::id() ?? $assetConfig->user_id,
            'created_checksum' => hash('sha256', json_encode(compact('prognosisType', 'assetScope', 'taxCountry')).'_created'),
            'updated_checksum' => hash('sha256', json_encode(compact('prognosisType', 'assetScope', 'taxCountry')).'_updated'),
        ]);
    }

    /**
     * Copy assets from asset configuration to simulation tables
     */
    protected function copyAssetsToSimulation(AssetConfiguration $assetConfig, SimulationConfiguration $simulationConfig, string $assetScope): void
    {
        $assetsQuery = $assetConfig->assets()->where('is_active', true);

        if ($assetScope === 'private') {
            $assetsQuery->where('group', 'private');
        } elseif ($assetScope === 'business') {
            $assetsQuery->where('group', 'business');
        }

        $assets = $assetsQuery->with('years')->get();

        /** @var \App\Models\Asset $asset */
        foreach ($assets as $asset) {
            // Create simulation asset
            $simulationAsset = SimulationAsset::create([
                'asset_configuration_id' => $simulationConfig->id,
                'name' => $asset->name,
                'code' => $asset->code,
                'description' => $asset->description,
                'asset_type' => $asset->asset_type,
                'group' => $asset->group,
                'tax_type' => optional($asset->assetType?->taxType)->type ?? 'none',
                'tax_property' => $asset->tax_property,
                'tax_country' => $asset->tax_country,
                'is_active' => $asset->is_active,
                'sort_order' => $asset->sort_order,
                'user_id' => Auth::id() ?? $asset->user_id,
                'team_id' => Auth::user()->currentTeam->id ?? $asset->team_id,
                'created_by' => Auth::id() ?? $asset->user_id,
                'updated_by' => Auth::id() ?? $asset->user_id,
                'created_checksum' => hash('sha256', json_encode($asset->toArray()).'_created'),
                'updated_checksum' => hash('sha256', json_encode($asset->toArray()).'_updated'),
            ]);

            // Copy asset years
            /** @var \App\Models\AssetYear $assetYear */
            foreach ($asset->years as $assetYear) {
                SimulationAssetYear::create([
                    'description' => $assetYear->description,
                    'user_id' => Auth::id() ?? $assetYear->user_id,
                    'team_id' => Auth::user()->currentTeam->id ?? $assetYear->team_id,
                    'year' => $assetYear->year,
                    'asset_id' => $simulationAsset->id,
                    'asset_configuration_id' => $simulationConfig->id,

                    // Income data

                    'income_amount' => $assetYear->income_amount,
                    'income_factor' => $assetYear->income_factor,
                    'income_rule' => 'standard',
                    'income_transfer' => 'none',
                    'income_source' => $asset->asset_type,
                    'income_changerate' => $assetYear->income_changerate,
                    'income_repeat' => true,

                    // Expense data
                    'expence_amount' => $assetYear->expence_amount,
                    'expence_factor' => $assetYear->expence_factor,
                    'expence_rule' => 'standard',
                    'expence_transfer' => 'none',
                    'expence_source' => $asset->asset_type,
                    'expence_changerate' => $assetYear->expence_changerate,
                    'expence_repeat' => true,

                    // Asset data
                    'asset_market_amount' => $assetYear->asset_market_amount,
                    'asset_acquisition_amount' => $assetYear->asset_acquisition_amount,
                    'asset_equity_amount' => $assetYear->asset_equity_amount,
                    'asset_taxable_initial_amount' => $assetYear->asset_taxable_initial_amount,
                    'asset_paid_amount' => $assetYear->asset_paid_amount,
                    'asset_changerate' => $assetYear->asset_changerate,
                    'asset_rule' => 'standard',
                    'asset_transfer' => 'none',
                    'asset_source' => $asset->asset_type,
                    'asset_repeat' => true,

                    // Mortgage data (if applicable)
                    'mortgage_amount' => 0,
                    'mortgage_years' => null,
                    'mortgage_interest_percent' => null,
                    'mortgage_interest_only_years' => null,
                    'mortgage_extra_downpayment_amount' => null,
                    'mortgage_gebyr_amount' => 0,
                    'mortgage_tax_deductable_amount' => 0,

                    // Audit fields
                    'created_by' => Auth::id() ?? $assetYear->user_id,
                    'updated_by' => Auth::id() ?? $assetYear->user_id,
                    'created_checksum' => hash('sha256', json_encode($assetYear->toArray()).'_created'),
                    'updated_checksum' => hash('sha256', json_encode($assetYear->toArray()).'_updated'),
                ]);
            }
        }
    }

    /**
     * Store detailed simulation results from PrognosisService dataH array
     *
     * @param  array<string, mixed>  $dataH  The dataH array from PrognosisService containing all calculated prognosis data
     */
    public function storePrognosisDataH(SimulationConfiguration $simulationConfig, array $dataH): void
    {
        Log::info('Storing prognosis dataH results', [
            'simulation_configuration_id' => $simulationConfig->id,
            'assets_count' => count($dataH),
        ]);

        foreach ($dataH as $assetName => $assetData) {
            if ($assetName === 'meta' || ! is_array($assetData)) {
                continue;
            }

            // Find the simulation asset by name
            $simulationAsset = SimulationAsset::where('asset_configuration_id', $simulationConfig->id)
                ->where('name', $assetName)
                ->first();

            if (! $simulationAsset) {
                Log::warning('Simulation asset not found for dataH entry', [
                    'asset_name' => $assetName,
                    'simulation_configuration_id' => $simulationConfig->id,
                ]);

                continue;
            }

            // Process each year's data
            foreach ($assetData as $year => $yearData) {
                if ($year === 'meta' || ! is_array($yearData) || ! is_numeric($year)) {
                    continue;
                }

                $this->storeYearData($simulationAsset, (int) $year, $yearData);
            }
        }

        Log::info('Prognosis dataH results stored successfully', [
            'simulation_configuration_id' => $simulationConfig->id,
        ]);
    }

    /**
     * Store a single year's data from dataH into simulation_asset_years
     *
     * @param  array<string, mixed>  $yearData
     */
    protected function storeYearData(SimulationAsset $simulationAsset, int $year, array $yearData): void
    {
        // Find or create the simulation asset year record
        $simulationAssetYear = SimulationAssetYear::updateOrCreate(
            [
                'asset_id' => $simulationAsset->id,
                'year' => $year,
            ],
            [
                'user_id' => Auth::id() ?? $simulationAsset->user_id,
                'team_id' => Auth::user()->currentTeam->id ?? $simulationAsset->team_id,
                'asset_configuration_id' => $simulationAsset->asset_configuration_id,

                // Income data
                'income_amount' => $yearData['income']['amount'] ?? null,
                'income_changerate' => $yearData['income']['changerate'] ?? null,
                'income_changerate_percent' => $yearData['income']['changeratePercent'] ?? null,
                'income_rule' => $yearData['income']['rule'] ?? null,
                'income_transfer' => $yearData['income']['transfer'] ?? null,
                'income_source' => $yearData['income']['source'] ?? null,
                'income_repeat' => $yearData['income']['repeat'] ?? false,
                'income_description' => $yearData['income']['description'] ?? null,

                // Expence data
                'expence_amount' => $yearData['expence']['amount'] ?? null,
                'expence_changerate' => $yearData['expence']['changerate'] ?? null,
                'expence_changerate_percent' => $yearData['expence']['changeratePercent'] ?? null,
                'expence_rule' => $yearData['expence']['rule'] ?? null,
                'expence_transfer' => $yearData['expence']['transfer'] ?? null,
                'expence_source' => $yearData['expence']['source'] ?? null,
                'expence_repeat' => $yearData['expence']['repeat'] ?? false,
                'expence_description' => $yearData['expence']['description'] ?? null,

                // Cashflow data
                'cashflow_after_tax_amount' => $yearData['cashflow']['afterTaxAmount'] ?? null,
                'cashflow_before_tax_amount' => $yearData['cashflow']['beforeTaxAmount'] ?? null,
                'cashflow_before_tax_aggregated_amount' => $yearData['cashflow']['beforeTaxAggregatedAmount'] ?? null,
                'cashflow_after_tax_aggregated_amount' => $yearData['cashflow']['afterTaxAggregatedAmount'] ?? null,
                'cashflow_tax_amount' => $yearData['cashflow']['taxAmount'] ?? null,
                'cashflow_tax_percent' => $yearData['cashflow']['taxPercent'] ?? null,
                'cashflow_rule' => $yearData['cashflow']['rule'] ?? null,
                'cashflow_transfer' => $yearData['cashflow']['transfer'] ?? null,
                'cashflow_source' => $yearData['cashflow']['source'] ?? null,
                'cashflow_repeat' => $yearData['cashflow']['repeat'] ?? false,
                'cashflow_description' => $yearData['cashflow']['description'] ?? null,

                // Asset data
                'asset_market_amount' => $yearData['asset']['marketAmount'] ?? null,
                'asset_market_mortgage_deducted_amount' => $yearData['asset']['marketMortgageDeductedAmount'] ?? null,
                'asset_acquisition_amount' => $yearData['asset']['acquisitionAmount'] ?? null,
                'asset_acquisition_initial_amount' => $yearData['asset']['acquisitionInitialAmount'] ?? null,
                'asset_equity_amount' => $yearData['asset']['equityAmount'] ?? null,
                'asset_equity_initial_amount' => $yearData['asset']['equityInitialAmount'] ?? null,
                'asset_paid_amount' => $yearData['asset']['paidAmount'] ?? null,
                'asset_paid_initial_amount' => $yearData['asset']['paidInitialAmount'] ?? null,
                'asset_transfered_amount' => $yearData['asset']['transferedAmount'] ?? null,
                'asset_taxable_percent' => $yearData['asset']['taxablePercent'] ?? null,
                'asset_taxable_amount' => $yearData['asset']['taxableAmount'] ?? null,
                'asset_taxable_initial_amount' => $yearData['asset']['taxableInitialAmount'] ?? null,
                'asset_taxable_amount_override' => $yearData['asset']['taxableAmountOverride'] ?? null,
                'asset_tax_percent' => $yearData['asset']['taxPercent'] ?? null,
                'asset_tax_amount' => $yearData['asset']['taxAmount'] ?? null,
                'asset_taxable_property_percent' => $yearData['asset']['taxablePropertyPercent'] ?? null,
                'asset_taxable_property_amount' => $yearData['asset']['taxablePropertyAmount'] ?? null,
                'asset_tax_property_percent' => $yearData['asset']['taxPropertyPercent'] ?? null,
                'asset_tax_property_amount' => $yearData['asset']['taxPropertyAmount'] ?? null,
                'asset_taxable_fortune_amount' => $yearData['asset']['taxableFortuneAmount'] ?? null,
                'asset_taxable_fortune_percent' => $yearData['asset']['taxableFortunePercent'] ?? null,
                'asset_tax_fortune_amount' => $yearData['asset']['taxFortuneAmount'] ?? null,
                'asset_tax_fortune_percent' => $yearData['asset']['taxFortunePercent'] ?? null,
                'asset_gjeldsfradrag_amount' => $yearData['asset']['gjeldsfradragAmount'] ?? null,
                'asset_changerate' => $yearData['asset']['changerate'] ?? null,
                'asset_changerate_percent' => $yearData['asset']['changeratePercent'] ?? null,
                'asset_rule' => $yearData['asset']['rule'] ?? null,
                'asset_transfer' => $yearData['asset']['transfer'] ?? null,
                'asset_source' => $yearData['asset']['source'] ?? null,
                'asset_repeat' => $yearData['asset']['repeat'] ?? true,
                'asset_description' => $yearData['asset']['description'] ?? null,

                // Mortgage data
                'mortgage_amount' => $yearData['mortgage']['amount'] ?? null,
                'mortgage_term_amount' => $yearData['mortgage']['termAmount'] ?? null,
                'mortgage_interest_amount' => $yearData['mortgage']['interestAmount'] ?? null,
                'mortgage_principal_amount' => $yearData['mortgage']['principalAmount'] ?? null,
                'mortgage_balance_amount' => $yearData['mortgage']['balanceAmount'] ?? null,
                'mortgage_extra_downpayment_amount' => $yearData['mortgage']['extraDownpaymentAmount'] ?? null,
                'mortgage_transfered_amount' => $yearData['mortgage']['transferedAmount'] ?? null,
                'mortgage_interest_percent' => $yearData['mortgage']['interestPercent'] ?? null,
                'mortgage_years' => $yearData['mortgage']['years'] ?? null,
                'mortgage_interest_only_years' => $yearData['mortgage']['interestOnlyYears'] ?? null,
                'mortgage_gebyr_amount' => $yearData['mortgage']['gebyrAmount'] ?? null,
                'mortgage_tax_deductable_amount' => $yearData['mortgage']['taxDeductableAmount'] ?? null,
                'mortgage_tax_deductable_percent' => $yearData['mortgage']['taxDeductablePercent'] ?? null,
                'mortgage_description' => $yearData['mortgage']['description'] ?? null,

                // Realization data
                'realization_amount' => $yearData['realization']['amount'] ?? null,
                'realization_taxable_amount' => $yearData['realization']['taxableAmount'] ?? null,
                'realization_tax_amount' => $yearData['realization']['taxAmount'] ?? null,
                'realization_tax_percent' => $yearData['realization']['taxPercent'] ?? null,
                'realization_tax_shield_amount' => $yearData['realization']['taxShieldAmount'] ?? null,
                'realization_tax_shield_percent' => $yearData['realization']['taxShieldPercent'] ?? null,
                'realization_description' => $yearData['realization']['description'] ?? null,

                // Yield data
                'yield_gross_percent' => $yearData['yield']['grossPercent'] ?? null,
                'yield_net_percent' => $yearData['yield']['netPercent'] ?? null,
                'yield_cap_percent' => $yearData['yield']['capPercent'] ?? null,

                // Potential data
                'potential_income_amount' => $yearData['potential']['incomeAmount'] ?? null,
                'potential_mortgage_amount' => $yearData['potential']['mortgageAmount'] ?? null,

                // Metrics data
                'metrics_roi_percent' => $yearData['metrics']['roiPercent'] ?? null,
                'metrics_total_return_amount' => $yearData['metrics']['totalReturnAmount'] ?? null,
                'metrics_total_return_percent' => $yearData['metrics']['totalReturnPercent'] ?? null,
                'metrics_coc_percent' => $yearData['metrics']['cocPercent'] ?? null,
                'metrics_noi' => $yearData['metrics']['noi'] ?? null,
                'metrics_grm' => $yearData['metrics']['grm'] ?? null,
                'metrics_dscr' => $yearData['metrics']['dscr'] ?? null,
                'metrics_ltv_percent' => $yearData['metrics']['ltvPercent'] ?? null,
                'metrics_de_ratio' => $yearData['metrics']['deRatio'] ?? null,
                'metrics_roe_percent' => $yearData['metrics']['roePercent'] ?? null,
                'metrics_roa_percent' => $yearData['metrics']['roaPercent'] ?? null,
                'metrics_pb_ratio' => $yearData['metrics']['pbRatio'] ?? null,
                'metrics_ev_ebitda' => $yearData['metrics']['evEbitda'] ?? null,
                'metrics_current_ratio' => $yearData['metrics']['currentRatio'] ?? null,

                // FIRE data
                'fire_percent' => $yearData['fire']['percent'] ?? null,
                'fire_income_amount' => $yearData['fire']['incomeAmount'] ?? null,
                'fire_expence_amount' => $yearData['fire']['expenceAmount'] ?? null,
                'fire_cashflow_amount' => $yearData['fire']['cashFlowAmount'] ?? null,
                'fire_saving_amount' => $yearData['fire']['savingAmount'] ?? null,
                'fire_saving_rate_percent' => $yearData['fire']['savingRate'] ?? null,

                // Audit fields
                'created_by' => Auth::id() ?? $simulationAsset->user_id,
                'updated_by' => Auth::id() ?? $simulationAsset->user_id,
                'created_checksum' => hash('sha256', json_encode($yearData).'_created'),
                'updated_checksum' => hash('sha256', json_encode($yearData).'_updated'),
            ]
        );
    }

    /**
     * Store detailed simulation results
     */
    protected function storeSimulationResults(SimulationConfiguration $simulationConfig, array $results): void
    {
        // For now, we'll store the results in the simulation configuration's tags field
        // In a more advanced implementation, you might want to create additional tables
        // to store the detailed yearly results

        $simulationConfig->update([
            'tags' => array_merge($simulationConfig->tags ?? [], [
                'simulation_completed',
                'total_years_'.count($results['yearly_data']),
                'fire_'.($results['summary']['fire_achieved'] ? 'achieved' : 'not_achieved'),
            ]),
            'updated_by' => Auth::id(),
            'updated_checksum' => hash('sha256', json_encode($results['summary']).'_results'),
        ]);

        Log::info('Simulation results stored', [
            'simulation_configuration_id' => $simulationConfig->id,
            'fire_achieved' => $results['summary']['fire_achieved'],
            'total_assets_end' => $results['summary']['total_assets_end'],
        ]);
    }

    /**
     * Map prognosis type to risk tolerance
     */
    protected function mapPrognosisTypeToRiskTolerance(string $prognosisType): string
    {
        return match ($prognosisType) {
            'negative' => 'conservative',
            'zero' => 'moderate_conservative',
            'realistic' => 'moderate',
            'positive' => 'moderate_aggressive',
            'tenpercent' => 'aggressive',
            'variable' => 'moderate_aggressive',
            default => 'moderate',
        };
    }

    /**
     * Get simulation results by configuration ID
     *
     * @return array<string, mixed>
     */
    public function getSimulationResults(int $simulationConfigurationId): array
    {
        $simulationConfig = SimulationConfiguration::with([
            'simulationAssets.simulationAssetYears',
        ])->findOrFail($simulationConfigurationId);

        // Reconstruct results from stored data
        // This is a simplified version - in a full implementation you might
        // want to store the complete results in a separate table

        return [
            'simulation_configuration' => $simulationConfig,
            'assets' => $simulationConfig->simulationAssets,
            'summary' => [
                'simulation_name' => $simulationConfig->name,
                'description' => $simulationConfig->description,
                'risk_tolerance' => $simulationConfig->risk_tolerance,
                'tags' => $simulationConfig->tags,
            ],
        ];
    }
}
