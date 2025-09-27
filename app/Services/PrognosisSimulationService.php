<?php

namespace App\Services;

use App\Models\AssetConfiguration;
use App\Models\PrognosisNew;
use App\Models\SimulationAsset;
use App\Models\SimulationAssetYear;
use App\Models\SimulationConfiguration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PrognosisSimulationService
{
    /**
     * Run a complete simulation based on parameters
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

        // Run the prognosis calculation
        $prognosisEngine = new PrognosisNew($assetConfiguration, $prognosisType, $assetScope);
        $results = $prognosisEngine->runSimulation();

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
            'death_age' => $assetConfig->death_age,
            'export_start_age' => $assetConfig->export_start_age,
            'public' => false,
            'risk_tolerance' => $this->mapPrognosisTypeToRiskTolerance($prognosisType),
            'tax_country' => $taxCountry,
            'prognosis_type' => $prognosisType,
            'group' => $assetScope,
            'tags' => [$prognosisType, $assetScope, $taxCountry, 'simulation'],
            'user_id' => Auth::id() ?? $assetConfig->user_id,
            'team_id' => Auth::user()?->currentTeam?->id ?? $assetConfig->team_id,
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
                'team_id' => Auth::user()?->currentTeam?->id ?? $asset->team_id,
                'created_by' => Auth::id() ?? $asset->user_id,
                'updated_by' => Auth::id() ?? $asset->user_id,
                'created_checksum' => hash('sha256', json_encode($asset->toArray()).'_created'),
                'updated_checksum' => hash('sha256', json_encode($asset->toArray()).'_updated'),
            ]);

            // Copy asset years
            foreach ($asset->years as $assetYear) {
                SimulationAssetYear::create([
                    'description' => $assetYear->description,
                    'user_id' => Auth::id() ?? $assetYear->user_id,
                    'team_id' => Auth::user()?->currentTeam?->id ?? $assetYear->team_id,
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
