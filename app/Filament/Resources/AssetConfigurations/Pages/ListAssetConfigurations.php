<?php

namespace App\Filament\Resources\AssetConfigurations\Pages;

use App\Filament\Resources\AssetConfigurations\Actions\CreateAiAssistedConfigurationAction;
use App\Filament\Resources\AssetConfigurations\AssetConfigurationResource;
use App\Services\AssetImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListAssetConfigurations extends ListRecords
{
    protected static string $resource = AssetConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAiAssistedConfigurationAction::make(),
            CreateAction::make(),
            Action::make('upload_assets')
                ->label('Upload Assets')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    FileUpload::make('json_file')
                        ->label('JSON Configuration File')
                        ->acceptedFileTypes(['application/json', 'text/json'])
                        ->maxSize(10240) // 10MB
                        ->required()
                        ->disk('local')
                        ->directory('asset-imports')
                        ->visibility('private')
                        ->multiple(false)
                        ->helperText('Upload your JSON asset configuration file (max 10MB, .json format)'),
                ])
                ->action(function (array $data) {
                    try {
                        $jsonFile = $data['json_file'];

                        // Handle file upload
                        if ($jsonFile instanceof TemporaryUploadedFile) {
                            $stored = $jsonFile->store('asset-imports', 'local');
                            $jsonFile = $stored;
                        } elseif (is_array($jsonFile)) {
                            $jsonFile = $jsonFile[0] ?? null;
                        }

                        if (! is_string($jsonFile) || $jsonFile === '') {
                            throw new \Exception('No valid JSON file found in upload');
                        }

                        // Get full file path
                        $filePath = Storage::disk('local')->path($jsonFile);

                        if (! file_exists($filePath)) {
                            throw new \Exception('Uploaded file not found');
                        }

                        // Import using the service
                        $importService = new AssetImportService(Auth::user());
                        $assetConfiguration = $importService->importFromFile($filePath);

                        // Clean up uploaded file
                        Storage::disk('local')->delete($jsonFile);

                        // Show success notification
                        Notification::make()
                            ->title('Assets Imported Successfully!')
                            ->body("Created asset configuration '{$assetConfiguration->name}' with {$assetConfiguration->assets()->count()} assets")
                            ->success()
                            ->duration(5000)
                            ->send();

                        // Refresh the table to show the new asset configuration
                        $this->dispatch('$refresh');

                    } catch (\Exception $e) {
                        Log::error('Asset upload failed', [
                            'error' => $e->getMessage(),
                            'user_id' => Auth::id(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('Import Failed')
                            ->body('Error: '.$e->getMessage())
                            ->danger()
                            ->duration(8000)
                            ->send();
                    }
                })
                ->modalHeading('Upload configuration')
                ->modalDescription('Upload a JSON configuration file to create a new configuration with all associated assets and yearly data.')
                ->modalSubmitActionLabel('Import Assets')
                ->modalWidth('lg'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
