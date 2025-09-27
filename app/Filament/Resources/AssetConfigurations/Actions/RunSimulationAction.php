<?php

namespace App\Filament\Resources\AssetConfigurations\Actions;

use App\Models\AssetConfiguration;
use App\Services\PrognosisSimulationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class RunSimulationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'run_simulation';
    }

    /**
     * Get available tax countries from the config/tax folder structure
     */
    protected function getAvailableTaxCountries(): array
    {
        $taxPath = config_path('tax');
        $countries = [];

        if (File::exists($taxPath)) {
            $directories = File::directories($taxPath);

            foreach ($directories as $directory) {
                $countryCode = basename($directory);

                // Map country codes to readable names
                $countryName = match ($countryCode) {
                    'no' => 'Norway',
                    'se' => 'Sweden',
                    'ch' => 'Switzerland',
                    'dk' => 'Denmark',
                    'us' => 'United States',
                    'en' => 'United Kingdom',
                    default => strtoupper($countryCode)
                };

                $countries[$countryCode] = $countryName;
            }
        }

        return $countries;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Run Simulation')
            ->icon('heroicon-o-calculator')
            ->color('primary')
            ->size('lg')
            ->visible(true)
            ->modalHeading('Run Financial Simulation')
            ->modalDescription('Create a detailed financial projection based on this asset configuration.')
            ->modalWidth('2xl')
            ->form([
                Section::make('Simulation Parameters')
                    ->description('Choose the simulation type, tax country, and scope for your financial projection.')
                    ->schema([
                        Radio::make('tax_country')
                            ->label('Tax Country')
                            ->options($this->getAvailableTaxCountries())
                            ->descriptions([
                                'no' => 'Norwegian tax system with wealth tax and progressive income tax',
                                'se' => 'Swedish tax system with capital gains tax and municipal tax',
                                'ch' => 'Swiss tax system with cantonal variations and wealth tax',
                            ])
                            ->default('no')
                            ->required()
                            ->inline(false)
                            ->columnSpanFull(),

                        Radio::make('prognosis_type')
                            ->label('Prognosis Type')
                            ->options([
                                'realistic' => 'Realistic',
                                'positive' => 'Positive',
                                'negative' => 'Negative',
                                'tenpercent' => 'Ten Percent',
                                'zero' => 'Zero Growth',
                                'variable' => 'Variable',
                            ])
                            ->descriptions([
                                'realistic' => 'Balanced economic assumptions',
                                'positive' => 'Optimistic economic growth',
                                'negative' => 'Conservative/pessimistic scenario',
                                'tenpercent' => 'High growth scenario',
                                'zero' => 'No growth scenario',
                                'variable' => 'Mixed scenario with variations',
                            ])
                            ->default('realistic')
                            ->required()
                            ->inline(false)
                            ->columnSpanFull(),

                        Radio::make('asset_scope')
                            ->label('Asset Scope')
                            ->options([
                                'private' => 'Private Only',
                                'business' => 'Business Only',
                                'both' => 'Both Private & Business',
                            ])
                            ->descriptions([
                                'private' => 'Personal assets and investments',
                                'business' => 'Business assets and company holdings',
                                'both' => 'Complete portfolio simulation',
                            ])
                            ->default('private')
                            ->required()
                            ->inline(false)
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(function (array $data, AssetConfiguration $record) {
                try {
                    DB::beginTransaction();

                    // Show progress notification
                    Notification::make()
                        ->title('Starting Simulation')
                        ->body('Creating detailed financial projection...')
                        ->info()
                        ->duration(3000)
                        ->send();

                    // Prepare simulation data
                    $simulationData = [
                        'asset_configuration_id' => $record->id,
                        'tax_country' => $data['tax_country'],
                        'prognosis_type' => $data['prognosis_type'],
                        'group' => $data['asset_scope'], // Map asset_scope to group field
                        'start_year' => date('Y'),
                        'end_year' => $record->birth_year + $record->death_age,
                    ];

                    // Run the simulation using the new Prognosis engine
                    $simulationService = new PrognosisSimulationService;
                    $results = $simulationService->runSimulation($simulationData);

                    $simulationConfigurationId = $results['simulation_configuration_id'];

                    DB::commit();

                    // Show success notification
                    Notification::make()
                        ->title('Simulation Completed Successfully')
                        ->body("Created detailed projection for {$record->name} from {$simulationData['start_year']} to {$simulationData['end_year']}")
                        ->success()
                        ->duration(5000)
                        ->send();

                    // Redirect to the pretty Simulation Dashboard for the new simulation
                    return redirect()->to(route('filament.admin.pages.simulation-dashboard', [
                        'configuration' => $record->getKey(),
                        'simulation' => $simulationConfigurationId,
                    ]));

                } catch (\Exception $e) {
                    DB::rollBack();

                    Log::error('Simulation failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'asset_configuration_id' => $record->id,
                        'user_id' => Auth::id(),
                        'simulation_data' => $data,
                    ]);

                    // Log the error for debugging

                    // Provide user-friendly error messages
                    $errorMessage = 'An unexpected error occurred during simulation.';

                    if (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'Maximum execution time')) {
                        $errorMessage = 'The simulation took too long to complete. Please try with a smaller date range.';
                    } elseif (str_contains($e->getMessage(), 'memory')) {
                        $errorMessage = 'The simulation requires too much memory. Please contact support.';
                    } elseif (str_contains($e->getMessage(), 'database')) {
                        $errorMessage = 'Database error occurred during simulation. Please try again.';
                    }

                    Notification::make()
                        ->title('Simulation Failed')
                        ->body($errorMessage)
                        ->danger()
                        ->duration(8000)
                        ->send();

                    // Don't re-throw to prevent black screen
                    return null;
                }
            });
    }
}
