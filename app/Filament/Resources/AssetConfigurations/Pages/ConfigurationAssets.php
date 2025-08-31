<?php

namespace App\Filament\Resources\AssetConfigurations\Pages;

use App\Filament\Resources\AssetConfigurations\AssetConfigurationResource;
use App\Models\AiInstruction;
use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetType;
use App\Services\AiEvaluationService;
use App\Services\AssetExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Locked;

class ConfigurationAssets extends ListRecords implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = AssetConfigurationResource::class;

    public ?AssetConfiguration $record = null;

    #[Locked]
    public ?array $aiEvaluationResults = null;

    public function mount(): void
    {
        $param = request()->route('record');
        $this->record = $param instanceof AssetConfiguration
            ? $param
            : AssetConfiguration::query()->findOrFail((int) $param);

        // Clear any existing AI results when mounting
        $this->aiEvaluationResults = null;
    }

    public function clearAiResults(): void
    {
        $this->aiEvaluationResults = null;
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.asset-owners.pages.owner-assets-header', [
            'record' => $this->record,
            'aiEvaluationResults' => $this->aiEvaluationResults,
        ]);
    }

    protected function getTableQuery(): Builder
    {
        return Asset::query()->where('asset_owner_id', $this->record->id);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('sort_order')->label('Sort Order')->alignLeft()->sortable(),
            TextColumn::make('name')->label('Name')->searchable()->sortable(),
            TextColumn::make('asset_type')->label('Type')->searchable()->sortable()->badge()->color('primary'),
            TextColumn::make('assetType.name')->label('Asset Type')->sortable(),
            TextColumn::make('tax_type')->label('Tax Type')->searchable()->sortable(),
            TextColumn::make('tax_property')->label('Tax Property')->searchable()->sortable(),
            TextColumn::make('description')->label('Description')->limit(60)->wrap(),
            IconColumn::make('is_active')->label('Active')->boolean(),
            TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected function getTableFiltersLayout(): FiltersLayout
    {
        return FiltersLayout::AboveContent;
    }

    protected function getTableHeaderActions(): array
    {
        return $this->getActions();
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('asset_type')
                ->label('Asset Type')
                ->options(fn () => AssetType::query()->active()->ordered()->pluck('name', 'type')->all())
                ->multiple()
                ->preload(),
            TernaryFilter::make('is_active')
                ->label('Status')
                ->placeholder('All')
                ->trueLabel('Active only')
                ->falseLabel('Inactive only'),
        ];
    }

    protected function getDefaultTableRecordsPerPage(): int
    {
        return 50;
    }

    protected function getTablePaginationPageOptions(): array
    {
        return [50, 100, 150];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->filtersLayout($this->getTableFiltersLayout())
            ->filters($this->getTableFilters())
            ->defaultSort('sort_order')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150])
            ->recordUrl(fn (\App\Models\Asset $asset) => route('filament.admin.resources.asset-years.index', [
                'owner' => $this->record->id,
                'asset' => $asset->id,
            ]));
    }

    protected function getActions(): array
    {
        return [
            Action::make('export_json')
                ->label('Export JSON')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    try {
                        // Export to JSON
                        $jsonContent = AssetExportService::toJsonString($this->record);
                        $exportDate = now()->format('Y-m-d');
                        $filename = $exportDate.'_'.\Illuminate\Support\Str::slug($this->record->name).'_'.$this->record->id.'.json';

                        // Return download response
                        return Response::streamDownload(
                            function () use ($jsonContent) {
                                echo $jsonContent;
                            },
                            $filename,
                            ['Content-Type' => 'application/json']
                        );

                    } catch (\Exception $e) {
                        Log::error('JSON export failed', [
                            'asset_owner_id' => $this->record->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('Export Failed')
                            ->body('Error: '.$e->getMessage())
                            ->danger()
                            ->duration(8000)
                            ->send();
                    }
                })
                ->tooltip('Download asset configuration as JSON file'),
            Action::make('ai_evaluation')
                ->label('AI Evaluation')
                ->icon('heroicon-o-cpu-chip')
                ->color('info')
                ->form([
                    CheckboxList::make('instruction_ids')
                        ->label('Select AI Instructions')
                        ->options(function () {
                            return AiInstruction::active()->ordered()->pluck('name', 'id')->toArray();
                        })
                        ->required()
                        ->helperText('Choose which AI instructions to use for evaluation')
                        ->columns(1),
                ])
                ->action(function (array $data) {
                    try {
                        $instructionIds = $data['instruction_ids'] ?? [];

                        if (empty($instructionIds)) {
                            throw new \InvalidArgumentException('Please select at least one AI instruction');
                        }

                        // Check if OpenAI API key is configured
                        if (! config('services.openai.api_key')) {
                            throw new \RuntimeException('OpenAI API key not configured. Please set OPENAI_API_KEY in your environment.');
                        }

                        $this->dispatch('ai-evaluation-started');

                        // Perform AI evaluation
                        $results = AiEvaluationService::evaluateMultiple($this->record, $instructionIds);

                        // Add instruction names to results
                        $instructions = AiInstruction::whereIn('id', $instructionIds)->get()->keyBy('id');
                        foreach ($results as &$result) {
                            $instructionId = $result['instruction_id'] ?? null;
                            if ($instructionId && isset($instructions[$instructionId])) {
                                $result['instruction_name'] = $instructions[$instructionId]->name;
                            }
                        }

                        $successCount = collect($results)->where('success', true)->count();
                        $totalCount = count($results);

                        // Store results in component property for display
                        $this->aiEvaluationResults = $results;

                        if ($successCount === $totalCount) {
                            Notification::make()
                                ->title('AI Evaluation Completed!')
                                ->body("Successfully completed {$successCount} evaluations. Results are displayed below.")
                                ->success()
                                ->duration(5000)
                                ->send();
                        } else {
                            $failedCount = $totalCount - $successCount;
                            Notification::make()
                                ->title('AI Evaluation Partially Completed')
                                ->body("Completed {$successCount} of {$totalCount} evaluations. {$failedCount} failed. Results are displayed below.")
                                ->warning()
                                ->duration(8000)
                                ->send();
                        }

                    } catch (\Exception $e) {
                        Log::error('AI evaluation failed', [
                            'asset_owner_id' => $this->record->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('AI Evaluation Failed')
                            ->body('Error: '.$e->getMessage())
                            ->danger()
                            ->duration(10000)
                            ->send();
                    }
                })
                ->modalHeading('AI Asset Evaluation')
                ->modalDescription('Select AI instructions to evaluate this asset portfolio. The AI will analyze the assets and provide insights and recommendations.')
                ->modalSubmitActionLabel('Start Evaluation')
                ->modalWidth('lg'),
            Action::make('clear_ai_results')
                ->label('Clear AI Results')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->visible(fn () => $this->aiEvaluationResults !== null)
                ->action(function () {
                    $this->clearAiResults();

                    Notification::make()
                        ->title('AI Results Cleared')
                        ->body('AI evaluation results have been cleared.')
                        ->success()
                        ->duration(3000)
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Clear AI Results')
                ->modalDescription('Are you sure you want to clear the AI evaluation results? This action cannot be undone.')
                ->modalSubmitActionLabel('Clear Results'),
            Action::make('back')
                ->label(__('Back to owners'))
                ->url(AssetConfigurationResource::getUrl('index')),
        ];
    }
}
