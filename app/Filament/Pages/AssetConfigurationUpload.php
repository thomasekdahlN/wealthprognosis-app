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
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AssetConfigurationUpload extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected string $view = 'filament.pages.asset-configuration-upload';

    protected static ?string $title = 'Asset Configuration Upload';

    protected static ?string $navigationLabel = 'Upload Asset Config';

    protected static ?int $navigationSort = 20;


    // protected static ?string $navigationGroup = 'Analysis';

    public array $data = [
        'prognosis' => 'realistic',
        'generate' => 'all',
    ];

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
        return 'Asset Configuration Upload & Analysis';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Radio::make('prognosis')
                    ->label('Prognosis Type')
                    ->options(function () {
                        return \App\Models\PrognosisType::query()
                            ->where('is_active', true)
                            ->orderBy('code')
                            ->pluck('label', 'code')
                            ->all();
                    })
                    ->descriptions([
                        'realistic' => 'Standard market expectations based on historical data and current economic indicators. Uses moderate growth rates and balanced risk assumptions.',
                        'positive' => 'Optimistic market prognosis with higher growth rates and favorable economic conditions. Assumes strong market performance and reduced volatility.',
                        'negative' => 'Pessimistic prognosis with lower growth rates and challenging market conditions. Accounts for economic downturns and increased market volatility.',
                        'tenpercent' => 'Fixed 10% annual growth rate for testing and comparison purposes. Provides a consistent benchmark for evaluating other prognosis types.',
                        'zero' => 'No growth prognosis for conservative planning and worst-case analysis. Assumes assets maintain their current value without appreciation.',
                        'variable' => 'Custom variable rates based on your specific configuration settings. Uses the change rates defined in your asset configuration file.',
                    ])
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
                    ->label('Asset Configuration File')
                    ->acceptedFileTypes(['application/json', 'text/json'])
                    ->maxSize(10240) // 10MB
                    ->required()
                    ->disk('local')
                    ->directory('asset-configs')
                    ->visibility('private')
                    ->multiple(false)
                    ->storeFileNamesIn('config_file_names')
                    ->helperText('Upload your JSON asset configuration file (max 10MB, .json)'),
            ])
            ->statePath('data');
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
                ->action(function () {
                    try {
                        $this->generateAnalysis();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error Generating Analysis')
                            ->body('Error: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function testMethod(): void
    {
        // Absolutely minimal test - just return

    }

    public function generateAnalysis(): void
    {
        try {
            // Debug notification to confirm method is called
            Notification::make()
                ->title('Starting Analysis Generation')
                ->body('Processing your request...')
                ->info()
                ->send();

            // Read the state from the form (Filament 4)
            $formData = $this->form->getState();

            // Validate required data
            if (empty($formData['config_file'])) {
                throw new \Exception('No configuration file uploaded. Available data: '.json_encode($formData));
            }

            if (empty($formData['prognosis'])) {
                throw new \Exception('No prognosis selected. Available data: '.json_encode($formData));
            }

            if (empty($formData['generate'])) {
                throw new \Exception('No analysis scope selected. Available data: '.json_encode($formData));
            }

            $configFile = $formData['config_file'];
            $scenario = (string) $formData['prognosis'];
            $generate = (string) $formData['generate'];

            // FileUpload state can be: string (path), array of strings, or TemporaryUploadedFile
            if ($configFile instanceof TemporaryUploadedFile) {
                // Move to target disk + directory and get the stored path
                $stored = $configFile->store('asset-configs', 'local');
                $configFile = $stored;
            } elseif (is_array($configFile)) {
                $configFile = $configFile[0] ?? null;
            }

            if (! is_string($configFile) || $configFile === '') {
                throw new \Exception('No valid configuration file found in upload');
            }

            // Debug: Show what we got
            Notification::make()
                ->title('Debug Info')
                ->body('Config file: '.$configFile.' | Prognosis: '.$scenario.' | Generate: '.$generate)
                ->info()
                ->send();

            // Handle file upload - configFile is in format "asset-configs/filename.json"
            $configFilePath = Storage::disk('local')->path($configFile);

            if (! file_exists($configFilePath)) {
                throw new \Exception('Configuration file not found at: '.$configFilePath);
            }

            // Debug: Show successful path
            Notification::make()
                ->title('File Found')
                ->body('Using file at: '.$configFilePath)
                ->success()
                ->send();

            // Step 1: Validate JSON file
            Notification::make()
                ->title('Step 1: Reading JSON file')
                ->body('Reading file content...')
                ->info()
                ->send();

            $jsonContent = file_get_contents($configFilePath);
            $jsonData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON file: '.json_last_error_msg());
            }

            Notification::make()
                ->title('Step 2: JSON validated')
                ->body('JSON file is valid, proceeding with export...')
                ->info()
                ->send();

            // Step 3: Prepare export
            Notification::make()
                ->title('Step 3: Preparing export')
                ->body('Setting up file paths and memory limits...')
                ->info()
                ->send();

            // Generate filename: ISO datetime + uploaded config base name + scenario
            $timestamp = now()->format('Y-m-d\TH-i-s'); // Use T separator, avoid colons for filesystem safety

            // Try to use the original uploaded filename from FileUpload mapping
            $originalName = pathinfo($configFile, PATHINFO_FILENAME);
            $fileNameMap = $formData['config_file_names'] ?? null;
            if (is_array($fileNameMap)) {
                if (is_string($configFile) && isset($fileNameMap[$configFile]) && is_string($fileNameMap[$configFile])) {
                    $originalName = pathinfo($fileNameMap[$configFile], PATHINFO_FILENAME);
                } else {
                    // Fallback: use the first value in the map if available
                    $first = reset($fileNameMap);
                    if (is_string($first)) {
                        $originalName = pathinfo($first, PATHINFO_FILENAME);
                    }
                }
            }

            $exportFileName = $timestamp.'_'.$originalName.'_'.$scenario.'.xlsx';
            $exportPath = storage_path('app/exports/'.$exportFileName);

            // Ensure exports directory exists
            if (! file_exists(dirname($exportPath))) {
                mkdir(dirname($exportPath), 0755, true);
            }

            // Set memory limit for large files
            ini_set('memory_limit', '512M');

            // Step 4: Generate Excel
            Notification::make()
                ->title('Step 4: Generating Excel')
                ->body('Creating Excel file using PrognosisExport2... This may take a moment.')
                ->info()
                ->send();

            // Generate the Excel file using the same export class as CLI
            new PrognosisExport2($configFilePath, $exportPath, $scenario, $generate);

            // Step 5: Check result
            Notification::make()
                ->title('Step 5: Checking result')
                ->body('Excel generation completed, verifying file...')
                ->info()
                ->send();

            // Check if file was created successfully
            if (! file_exists($exportPath)) {
                throw new \Exception('Failed to generate Excel file');
            }

            // Download the file
            $this->downloadFile($exportPath, $exportFileName);

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
    }

    private function downloadFile(string $filePath, string $fileName): void
    {
        // Use JavaScript to trigger download
        $this->dispatch('download-file',
            url: route('download.analysis', ['file' => basename($filePath)]),
            filename: $fileName,
        );
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
