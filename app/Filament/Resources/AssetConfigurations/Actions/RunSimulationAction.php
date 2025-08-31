<?php

namespace App\Filament\Resources\AssetConfigurations\Actions;

use App\Models\AssetConfiguration;
use App\Models\SimulationConfiguration;
use App\Services\PrognosisSimulationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunSimulationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'run_simulation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Run Simulation')
            ->icon('heroicon-o-calculator')
            ->color('primary')
            ->size('lg')
            ->modalHeading('Run Financial Simulation')
            ->modalDescription('Create a detailed financial projection based on this asset configuration.')
            ->modalWidth('2xl')
            ->form([
                Section::make('Simulation Parameters')
                    ->description('Choose the simulation type and scope for your financial projection.')
                    ->schema([
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
                                'both' => 'Both Private & Business',
                                'private' => 'Private Only',
                                'business' => 'Business Only',
                            ])
                            ->descriptions([
                                'both' => 'Complete portfolio simulation',
                                'private' => 'Personal assets and investments',
                                'business' => 'Business assets and company holdings',
                            ])
                            ->default('both')
                            ->required()
                            ->inline(false)
                            ->columnSpanFull(),
                    ])
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
                        'prognosis_type' => $data['prognosis_type'],
                        'asset_scope' => $data['asset_scope'],
                        'start_year' => date('Y'),
                        'end_year' => $record->birth_year + $record->death_age,
                    ];

                    // Run the simulation using the new Prognosis engine
                    $simulationService = new PrognosisSimulationService();
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

                    // For now, just show success - in future we can redirect to simulation results page
                    return null;

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
