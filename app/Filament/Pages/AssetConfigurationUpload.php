<?php

namespace App\Filament\Pages;

use App\Exports\PrognosisExport2;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AssetConfigurationUpload extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $title = 'Configuration upload';

    protected static ?string $navigationLabel = 'Upload configuration';

    protected static ?int $navigationSort = 20;

    // protected static ?string $navigationGroup = 'Analysis';

    public function mount(): void
    {
        // Initialize data
    }

    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function getHeading(): string
    {
        return 'Configuration upload & analysis';
    }

    public function getSubheading(): ?string
    {
        return 'Upload your JSON asset configuration file and generate Excel prognosis reports';
    }

    // Remove form method - use simple Livewire properties instead

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_analysis')
                ->label('Generate Excel Analysis')
                ->icon('heroicon-m-document-arrow-down')
                ->color('primary')
                ->size('lg')
                ->form([
                    Radio::make('prognosis')
                        ->label('Prognosis Type')
                        ->options(fn () => \App\Models\PrognosisType::query()->where('is_active', true)->orderBy('code')->pluck('label', 'code')->all())
                        ->required()
                        ->default('realistic'),
                    Select::make('generate')
                        ->label('Analysis Scope')
                        ->options([
                            'all' => 'All - Complete analysis (private + company)',
                            'private' => 'Private - Personal assets only',
                            'company' => 'Company - Business assets only',
                        ])
                        ->required()
                        ->default('all'),
                    FileUpload::make('config_file')
                        ->label('Configuration file')
                        ->acceptedFileTypes(['application/json', 'text/json'])
                        ->maxSize(10240)
                        ->required()
                        ->disk('local')
                        ->directory('asset-configs')
                        ->visibility('private')
                        ->multiple(false)
                        ->storeFileNamesIn('config_file_names')
                        ->helperText('Upload your JSON configuration file (max 10MB, .json)'),
                ])
                ->action(function (array $data) {
                    try {
                        // Debug notification to confirm method is called
                        Notification::make()
                            ->title('Starting Analysis Generation')
                            ->body('Processing your request...')
                            ->info()
                            ->send();

                        $configFile = $data['config_file'] ?? null;
                        $scenario = (string) ($data['prognosis'] ?? 'realistic');
                        $generate = (string) ($data['generate'] ?? 'all');

                        if ($configFile instanceof TemporaryUploadedFile) {
                            $stored = $configFile->store('asset-configs', 'local');
                            $configFile = $stored;
                        } elseif (is_array($configFile)) {
                            $configFile = $configFile[0] ?? null;
                        }

                        if (! is_string($configFile) || $configFile === '') {
                            throw new \Exception('No valid configuration file found in upload');
                        }

                        $timestamp = now()->format('Y-m-d\TH-i-s');
                        $originalName = pathinfo($configFile, PATHINFO_FILENAME);
                        $fileNameMap = $data['config_file_names'] ?? null;
                        if (is_array($fileNameMap)) {
                            if (is_string($configFile) && isset($fileNameMap[$configFile]) && is_string($fileNameMap[$configFile])) {
                                $originalName = pathinfo($fileNameMap[$configFile], PATHINFO_FILENAME);
                            } else {
                                $first = reset($fileNameMap);
                                if (is_string($first)) {
                                    $originalName = pathinfo($first, PATHINFO_FILENAME);
                                }
                            }
                        }

                        $exportFileName = $timestamp.'_'.$originalName.'_'.$scenario.'.xlsx';
                        $exportPath = storage_path('app/exports/'.$exportFileName);

                        if (! file_exists(dirname($exportPath))) {
                            mkdir(dirname($exportPath), 0755, true);
                        }

                        ini_set('memory_limit', '512M');

                        // Read and validate JSON
                        $configFilePath = Storage::disk('local')->path($configFile);
                        if (! file_exists($configFilePath)) {
                            throw new \Exception("Configuration file not found!\n\nFile: {$configFilePath}\n\nPlease check that the file was uploaded correctly.");
                        }

                        $jsonContent = file_get_contents($configFilePath);
                        if ($jsonContent === false) {
                            throw new \Exception("Failed to read configuration file!\n\nFile: {$configFilePath}\n\nPlease check file permissions.");
                        }

                        $jsonData = json_decode($jsonContent, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $jsonError = json_last_error_msg();
                            throw new \Exception("Invalid JSON in configuration file!\n\nJSON Error: {$jsonError}\n\nPlease validate your JSON file using a JSON validator.\n\nCommon issues:\n- Missing or extra commas\n- Unquoted keys or values\n- Trailing commas before } or ]\n- Invalid escape sequences");
                        }

                        if (! is_array($jsonData)) {
                            throw new \Exception("Invalid JSON structure!\n\nThe JSON file must contain a valid configuration object.");
                        }

                        // Generate the Excel file using the same export class as CLI
                        new PrognosisExport2($configFilePath, $exportPath, $scenario, $generate);

                        if (! file_exists($exportPath)) {
                            throw new \Exception('Failed to generate Excel file');
                        }

                        // Trigger download via global listener
                        $url = \URL::signedRoute('download.analysis', ['file' => basename($exportPath)]);
                        $this->dispatch('download-file', url: $url, filename: $exportFileName);

                        Notification::make()
                            ->title('Analysis Generated Successfully')
                            ->body("Excel analysis for prognosis '{$scenario}' has been generated and downloaded.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Analysis Generation Failed')
                            ->body('Error: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
