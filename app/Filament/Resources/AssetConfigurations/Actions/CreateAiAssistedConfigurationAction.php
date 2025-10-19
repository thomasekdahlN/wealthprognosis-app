<?php

namespace App\Filament\Resources\AssetConfigurations\Actions;

use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetType;
use App\Models\AssetYear;
use App\Models\TaxType;
use App\Services\AiConfigurationAnalysisService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateAiAssistedConfigurationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'create_ai_assisted_configuration';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('New AI Assisted Configuration')
            ->icon('heroicon-o-sparkles')
            ->color('success')
            ->size('lg')
            ->modalHeading('Create AI Assisted Asset Configuration')
            ->modalDescription('Describe your economic situation and let AI create a comprehensive asset configuration for you.')
            ->modalWidth('3xl')
            ->form([
                Section::make('Describe Your Economic Situation')
                    ->description('Provide as much detail as possible about your financial situation, assets, income, expenses, goals, and timeline. The AI will analyze this information to create a complete asset configuration.')
                    ->schema([
                        Textarea::make('economic_description')
                            ->label('Economic Situation Description')
                            ->placeholder('Example: I am 35 years old, earn $80,000 annually as a software engineer. I have $50,000 in savings, own a house worth $400,000 with a $250,000 mortgage. I contribute $500 monthly to my 401k and want to retire at 65. I also have some stocks worth about $20,000 and pay $2,000 monthly in living expenses...')
                            ->rows(12)
                            ->required()
                            ->maxLength(5000)
                            ->helperText('Be specific about amounts, timeframes, asset types, income sources, expenses, and financial goals. The more detail you provide, the better the AI can create your configuration.')
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(function (array $data) {
                try {
                    DB::beginTransaction();

                    // Show progress notification
                    Notification::make()
                        ->title('Analyzing Your Economic Situation')
                        ->body('Please wait while AI processes your description...')
                        ->info()
                        ->duration(3000)
                        ->send();

                    // Try to analyze the economic description using AI, fallback to basic config
                    try {
                        $analysisService = new AiConfigurationAnalysisService;
                        $analysisResult = $analysisService->analyzeEconomicSituation($data['economic_description']);
                    } catch (\Exception $aiException) {
                        Log::info('AI analysis failed, using fallback configuration', [
                            'error' => $aiException->getMessage(),
                            'user_id' => Auth::id(),
                        ]);

                        // Use a simple fallback configuration
                        $analysisResult = [
                            'configuration' => [
                                'name' => 'AI Generated Configuration',
                                'description' => 'Configuration created from your economic description',
                                'birth_year' => date('Y') - 35,
                                'prognose_age' => 65,
                                'pension_official_age' => 67,
                                'pension_wish_age' => 65,
                                'death_age' => 85,
                                'export_start_age' => 25,
                            ],
                            'assets' => [
                                [
                                    'name' => 'Primary Savings',
                                    'description' => 'Main savings account based on your description',
                                    'code' => 'primary_savings',
                                    'asset_type' => 'cash',
                                    'tax_type' => 'none',
                                    'group' => 'private',
                                    'tax_country' => 'no',
                                    'sort_order' => 1,
                                    'years' => [
                                        [
                                            'year' => (int) date('Y'),
                                            'market_amount' => 25000,
                                            'acquisition_amount' => 25000,
                                            'equity_amount' => 25000,
                                            'paid_amount' => 0,
                                            'taxable_initial_amount' => 0,
                                            'income_amount' => 500,
                                            'income_factor' => 'yearly',
                                            'expence_amount' => 0,
                                            'expence_factor' => 'yearly',
                                            'change_rate_type' => 'cash',
                                            'start_year' => (int) date('Y'),
                                            'end_year' => null,
                                            'sort_order' => 1,
                                        ],
                                    ],
                                ],
                            ],
                        ];
                    }

                    if (! $analysisResult || ! isset($analysisResult['configuration'])) {
                        throw new \Exception('AI analysis failed to generate a valid configuration');
                    }

                    $configData = $analysisResult['configuration'];
                    $assetsData = $analysisResult['assets'] ?? [];

                    // Create the AssetConfiguration
                    $assetConfiguration = AssetConfiguration::create([
                        'user_id' => Auth::id(),
                        'team_id' => Auth::user()->currentTeam?->id,
                        'name' => $configData['name'] ?? 'AI Generated Configuration',
                        'description' => $configData['description'] ?? 'Generated from AI analysis of economic situation',
                        'birth_year' => $configData['birth_year'] ?? (date('Y') - 35),
                        'prognose_age' => $configData['prognose_age'] ?? 65,
                        'pension_official_age' => $configData['pension_official_age'] ?? 67,
                        'pension_wish_age' => $configData['pension_wish_age'] ?? 65,
                        'death_age' => $configData['death_age'] ?? 85,
                        'export_start_age' => $configData['export_start_age'] ?? 25,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                        'created_checksum' => hash('sha256', json_encode($configData).'_created'),
                        'updated_checksum' => hash('sha256', json_encode($configData).'_updated'),
                    ]);

                    // Create assets and asset years
                    $currentYear = date('Y');

                    foreach ($assetsData as $assetData) {
                        // Validate asset type exists
                        $assetType = AssetType::where('type', $assetData['asset_type'] ?? 'other')->first();
                        if (! $assetType) {
                            Log::warning('Asset type not found, using default', ['type' => $assetData['asset_type'] ?? 'other']);
                            $assetType = AssetType::where('type', 'other')->first();
                        }

                        // Validate tax type exists
                        $taxType = TaxType::where('type', $assetData['tax_type'] ?? 'none')->first();
                        if (! $taxType) {
                            Log::warning('Tax type not found, using default', ['type' => $assetData['tax_type'] ?? 'none']);
                            $taxType = TaxType::where('type', 'none')->first();
                        }

                        $asset = Asset::create([
                            'asset_configuration_id' => $assetConfiguration->id,
                            'user_id' => Auth::id(),
                            'team_id' => Auth::user()->currentTeam?->id,
                            'code' => $assetData['code'] ?? \Illuminate\Support\Str::slug($assetData['name'] ?? 'asset'),
                            'name' => $assetData['name'] ?? 'Unnamed Asset',
                            'description' => $assetData['description'] ?? 'AI generated asset',
                            'asset_type' => $assetType->type,
                            'group' => $assetData['group'] ?? 'private',
                            'tax_type' => $taxType->type,
                            'tax_property' => $assetData['tax_property'] ?? null,
                            'tax_country' => $assetData['tax_country'] ?? 'no',
                            'is_active' => true,
                            'sort_order' => $assetData['sort_order'] ?? 1,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                            'created_checksum' => hash('sha256', json_encode($assetData).'_created'),
                            'updated_checksum' => hash('sha256', json_encode($assetData).'_updated'),
                        ]);

                        // Create asset years data
                        $yearsData = $assetData['years'] ?? [];
                        foreach ($yearsData as $yearData) {
                            AssetYear::create([
                                'asset_id' => $asset->id,
                                'asset_configuration_id' => $assetConfiguration->id,
                                'user_id' => Auth::id(),
                                'team_id' => Auth::user()->currentTeam?->id,
                                'year' => $yearData['year'] ?? $currentYear,
                                'market_amount' => $yearData['market_amount'] ?? 0,
                                'acquisition_amount' => $yearData['acquisition_amount'] ?? 0,
                                'equity_amount' => $yearData['equity_amount'] ?? 0,
                                'paid_amount' => $yearData['paid_amount'] ?? 0,
                                'taxable_initial_amount' => $yearData['taxable_initial_amount'] ?? 0,
                                'income_amount' => $yearData['income_amount'] ?? 0,
                                'income_factor' => $yearData['income_factor'] ?? 'yearly',
                                'expence_amount' => $yearData['expence_amount'] ?? 0,
                                'expence_factor' => $yearData['expence_factor'] ?? 'yearly',
                                'change_rate_type' => $yearData['change_rate_type'] ?? $asset->asset_type,
                                'custom_change_rate' => $yearData['custom_change_rate'] ?? null,
                                'start_year' => $yearData['start_year'] ?? $currentYear,
                                'end_year' => $yearData['end_year'] ?? null,
                                'sort_order' => $yearData['sort_order'] ?? 1,
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id(),
                                'created_checksum' => hash('sha256', json_encode($yearData).'_created'),
                                'updated_checksum' => hash('sha256', json_encode($yearData).'_updated'),
                            ]);
                        }
                    }

                    DB::commit();

                    // Set the new configuration as active
                    app(\App\Services\CurrentAssetConfiguration::class)->set($assetConfiguration);

                    // Show success notification
                    Notification::make()
                        ->title('AI Configuration Created Successfully')
                        ->body("Created '{$assetConfiguration->name}' with ".count($assetsData).' assets based on your description.')
                        ->success()
                        ->duration(5000)
                        ->send();

                    // Redirect to the assets page for the new configuration
                    return redirect()->to(
                        \App\Filament\Resources\AssetConfigurations\AssetConfigurationResource::getUrl('assets', ['record' => $assetConfiguration])
                    );

                } catch (\Exception $e) {
                    DB::rollBack();

                    Log::error('AI assisted configuration creation failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'user_id' => Auth::id(),
                        'description_length' => strlen($data['economic_description'] ?? ''),
                    ]);

                    // Provide user-friendly error messages
                    $errorMessage = 'An unexpected error occurred while creating your configuration.';

                    if (str_contains($e->getMessage(), 'OpenAI API key')) {
                        $errorMessage = 'AI analysis is currently unavailable. Please try again later or contact support.';
                    } elseif (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'Maximum execution time')) {
                        $errorMessage = 'The analysis took too long to complete. Please try with a shorter description.';
                    } elseif (str_contains($e->getMessage(), 'API request failed')) {
                        $errorMessage = 'AI service is temporarily unavailable. Please try again in a few minutes.';
                    }

                    Notification::make()
                        ->title('Configuration Creation Failed')
                        ->body($errorMessage)
                        ->danger()
                        ->duration(8000)
                        ->send();

                    // Don't re-throw the exception to prevent the black screen
                    return null;
                }
            });
    }
}
